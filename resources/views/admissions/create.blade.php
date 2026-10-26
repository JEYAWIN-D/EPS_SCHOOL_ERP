@extends('layouts.app')

@section('title', 'New Student Admission Form — Erode Public School (' . ($academicYear?->name ?? '2026-2027') . ')')

@push('head')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --school-maroon: #8C2826;
      --school-maroon-dark: #731E1C;
      --school-maroon-light: #FFF5F5;
      --school-maroon-border: #FECACA;
      --school-gold: #C8973A;
      --school-gold-light: #FEF9EE;
      --school-gold-border: #FDE68A;
    }
    .admission-form-wrapper {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;
    }
    .admission-form-wrapper input,
    .admission-form-wrapper select,
    .admission-form-wrapper textarea,
    .admission-form-wrapper button {
      font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      -webkit-font-smoothing: antialiased;
    }
    .admission-form-wrapper h1,
    .admission-form-wrapper h2,
    .admission-form-wrapper h3,
    .admission-form-wrapper h4 {
      font-family: 'Plus Jakarta Sans', sans-serif !important;
      letter-spacing: -0.02em;
      -webkit-font-smoothing: antialiased;
    }
    .admission-form-wrapper .tabular-nums {
      font-variant-numeric: tabular-nums;
      font-feature-settings: "tnum" 1;
    }
    .step-badge {
      display: inline-flex !important;
      align-items: center !important;
      gap: 0.35rem !important;
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.03em !important;
      text-transform: uppercase !important;
      background-color: #FFF5F5 !important;
      color: #8C2826 !important;
      border: 1px solid #FECACA !important;
      padding: 0.3rem 0.8rem !important;
      border-radius: 9999px !important;
      box-shadow: 0 1px 2px rgba(140, 40, 38, 0.05) !important;
    }
    .admission-form-wrapper input:focus,
    .admission-form-wrapper select:focus,
    .admission-form-wrapper textarea:focus {
      border-color: #8C2826 !important;
      --tw-ring-color: rgba(140, 40, 38, 0.18) !important;
    }
  </style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-6 admission-form-wrapper" x-init="initAdmission()" x-data="{
  classList: {{ json_encode($classes->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
  sectionsList: {{ json_encode($sections->map(fn($s) => ['id' => $s->id, 'class_id' => $s->class_id, 'name' => $s->name])) }},
  standardFees: {{ json_encode($standardFees) }},
  activitiesData: {{ json_encode($activities) }},
  admissionKitsData: {{ json_encode($admissionKits ?? []) }},
  transportRoutesData: {{ json_encode($transportRoutes ?? []) }},
  selectedClassId: '{{ old("class_id", request("class_id", "")) }}',
  selectedSectionId: '{{ old("section_id", "") }}',

  // Books & Notes Master Checklist State
  academicYearId: '{{ $academicYear?->id ?? "" }}',
  academicYearsList: {{ json_encode(($academicYears ?? collect([$academicYear]))->map(fn($y) => ['id' => $y->id, 'name' => $y->name, 'is_current' => $y->is_current])) }},
  academicGroupsList: {{ json_encode(($academicGroups ?? collect([]))->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'code' => $g->code])) }},
  selectedBookNoteGroupId: '',
  selectedGender: '{{ old('gender', '') }}',
  bookNoteList: { books: [], notes: [], uniforms: [] },
  bookNoteLoading: false,
  curriculumTab: 'all', // 'all' | 'books' | 'notes' | 'uniforms'
  curriculumExpanded: false,

  get selectedYearName() {
    let y = this.academicYearsList.find(x => x.id == this.academicYearId);
    return y ? y.name : '2026–2027';
  },

  async fetchBooksNotesChecklist() {
    if (!this.selectedClassId) {
      this.bookNoteList = { books: [], notes: [], uniforms: [] };
      return;
    }
    if (this.isSeniorSecondary && !this.selectedBookNoteGroupId) {
      this.bookNoteList = { books: [], notes: [], uniforms: [] };
      return;
    }
    this.bookNoteLoading = true;
    try {
      let params = new URLSearchParams({
        academicYearId: this.academicYearId,
        classId: this.selectedClassId,
      });
      if (this.selectedGender) {
        params.append('gender', this.selectedGender);
      }
      if (this.selectedBookNoteGroupId) {
        params.append('groupId', this.selectedBookNoteGroupId);
      }
      let res = await fetch(`{{ route('api.books-notes.checklist') }}?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
      });
      let json = await res.json();
      if (json.success) {
        this.bookNoteList = {
          books: (json.books || []).map(b => ({ ...b, is_issued: true })),
          notes: (json.notes || []).map(n => ({ ...n, is_issued: true })),
          uniforms: (json.uniforms || []).map(u => ({ ...u, is_issued: true })),
        };
      } else {
        this.bookNoteList = { books: [], notes: [], uniforms: [] };
      }
    } catch (e) {
      console.error('Error fetching books & notes checklist:', e);
      this.bookNoteList = { books: [], notes: [], uniforms: [] };
    } finally {
      this.bookNoteLoading = false;
    }
  },

  setAllBookNoteIssuance(issued) {
    this.bookNoteList.books.forEach(b => b.is_issued = issued);
    this.bookNoteList.notes.forEach(n => n.is_issued = issued);
    (this.bookNoteList.uniforms || []).forEach(u => u.is_issued = issued);
  },

  get bookNoteIssuedTotalUnits() {
    let b = this.bookNoteList.books.filter(x => x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    let n = this.bookNoteList.notes.filter(x => x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    let u = (this.bookNoteList.uniforms || []).filter(x => x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    return b + n + u;
  },

  get bookNotePendingTotalUnits() {
    let b = this.bookNoteList.books.filter(x => !x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    let n = this.bookNoteList.notes.filter(x => !x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    let u = (this.bookNoteList.uniforms || []).filter(x => !x.is_issued).reduce((a,c) => a + (c.quantity || 1), 0);
    return b + n + u;
  },

  get bookNoteTotalUnits() {
    let b = this.bookNoteList.books.reduce((a,c) => a + (c.quantity || 1), 0);
    let n = this.bookNoteList.notes.reduce((a,c) => a + (c.quantity || 1), 0);
    let u = (this.bookNoteList.uniforms || []).reduce((a,c) => a + (c.quantity || 1), 0);
    return b + n + u;
  },

  syncGroupFromStream() {
    if (this.isSeniorSecondary) {
      let st = (this.selectedStreamGroup || '').toUpperCase();
      let matchGrp = null;
      if (st.includes('GROUP C') || st.includes('ACCOUNT') || st.includes('COMMERCE') || st.includes('CA')) {
        matchGrp = this.academicGroupsList.find(g => (g.code && g.code.includes('ACCOUNT')) || g.name.includes('ACCOUNT'));
      } else if (st.includes('CS') || st.includes('COMPUTER') || st.includes('ENGINEERING')) {
        matchGrp = this.academicGroupsList.find(g => (g.code && g.code === 'CS') || g.name.includes('CS'));
      } else if (st.includes('GROUP A') || st.includes('GROUP B') || st.includes('BIOLOGY') || st.includes('RESEARCH') || st.includes('MEDICAL') || st.includes('BIO')) {
        matchGrp = this.academicGroupsList.find(g => (g.code && g.code.includes('BIO')) || g.name.includes('BIO'));
      }
      if (matchGrp) {
        this.selectedBookNoteGroupId = matchGrp.id;
      }
    } else {
      this.selectedBookNoteGroupId = '';
    }
    this.fetchBooksNotesChecklist();
  },

  initAdmission() {
    this.$watch('selectedClassId', () => {
      this.syncGroupFromStream();
      this.fetchBooksNotesChecklist();
      if (!this.userHasEditedAmountCollected) {
        this.userAmountCollected = String(this.term1Amount);
      }
      if (!this.userHasEditedConcession && this.concessionType !== 'none') {
        this.concessionAmountInput = String(this.calculatedConcession);
      }
    });
    this.$watch('paymentTerms', () => {
      if (!this.userHasEditedAmountCollected) {
        this.userAmountCollected = String(this.term1Amount);
      }
    });
    this.$watch('concessionType', (newVal) => {
      if (newVal === 'none') {
        this.concessionAmountInput = '0';
      } else if (!this.userHasEditedConcession) {
        this.concessionAmountInput = String(this.calculatedConcession);
      }
      this.isPaymentVerified = false;
    });
    this.$watch('selectedGender', () => {
      this.fetchBooksNotesChecklist();
    });
    this.$watch('selectedStreamGroup', () => {
      this.syncGroupFromStream();
    });
    this.$watch('selectedBookNoteGroupId', () => {
      this.fetchBooksNotesChecklist();
    });
    this.$watch('academicYearId', () => {
      this.fetchBooksNotesChecklist();
    });
    if (this.selectedClassId) {
      this.syncGroupFromStream();
      this.fetchBooksNotesChecklist();
    }
    if (!this.userAmountCollected && this.selectedClassId) {
      this.userAmountCollected = String(this.term1Amount);
    }
  },

  // After School Program (ASP) State — replaces Hostel per school requirement
  aspRequired: {{ old("is_asp") ? 'true' : 'false' }},
  aspTerm: '{{ old("asp_term", "") }}',
  aspTermFee: 4000,    // per term ₹4,000 (Term I: Jul–Oct, Term II: Nov–Feb)
  aspInfoOpen: false,  // toggle timetable preview

  // School Transport Facility State (Stopping, Km, Fee)
  transportRequired: {{ old("transport_route_id") ? 'true' : 'false' }},
  selectedRouteId: '{{ old("transport_route_id", "") }}',
  selectedStopId: '{{ old("transport_stop_id", "") }}',
  selectedDropStopId: '{{ old("transport_drop_stop_id", "") }}',
  transportPickupTime: '{{ old("transport_pickup_time", "") }}',
  transportDropTime: '{{ old("transport_drop_time", "") }}',

  // Concession Categories State (Staff kid, Topper, Sports, Single shot, Referral, Sibling)
  concessionType: '{{ old("concession_type", "none") }}',
  concessionAmountInput: '{{ old("concession_amount", "") }}',
  concessionRemarks: '{{ old("concession_remarks", "") }}',
  userHasEditedConcession: {{ old("concession_amount") !== null && old("concession_amount") !== '' ? 'true' : 'false' }},

  // Sibling in Same School
  hasSibling: {{ old('has_sibling') || old('sibling_name') || old('sibling_admission_no') || old('sibling_roll_no') ? 'true' : 'false' }},
  siblingRollNo: '{{ old("sibling_roll_no", old("sibling_admission_no", "")) }}',
  siblingName: '{{ old("sibling_name", "") }}',
  siblingClass: '{{ old("sibling_class", "") }}',
  siblingAdmissionNo: '{{ old("sibling_admission_no", "") }}',
  siblingLookupStatus: '', // '', 'loading', 'found', 'not_found', 'error'
  siblingLookupMessage: '',

  async fetchSiblingDetails() {
    let roll = (this.siblingRollNo || '').trim();
    if (!roll) {
      this.siblingName = '';
      this.siblingClass = '';
      this.siblingAdmissionNo = '';
      this.siblingLookupStatus = '';
      this.siblingLookupMessage = '';
      return;
    }
    this.siblingLookupStatus = 'loading';
    this.siblingLookupMessage = 'Fetching student details...';
    try {
      let res = await fetch(`{{ route('admissions.sibling-lookup') }}?roll_number=${encodeURIComponent(roll)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      let data = await res.json();
      if (data.found && data.student) {
        this.siblingName = data.student.name;
        this.siblingClass = data.student.class_section;
        this.siblingAdmissionNo = data.student.admission_no || data.student.roll_number;
        this.siblingLookupStatus = 'found';
        this.siblingLookupMessage = `Found: ${data.student.name} (${data.student.class_section})`;
      } else {
        this.siblingName = '';
        this.siblingClass = '';
        this.siblingAdmissionNo = '';
        this.siblingLookupStatus = 'not_found';
        this.siblingLookupMessage = data.message || 'No student found with this Roll No.';
      }
    } catch (e) {
      this.siblingLookupStatus = 'error';
      this.siblingLookupMessage = 'Error connecting to student database.';
    }
  },

  // Extra Curricular Activities (ECA) — Complimentary per school requirement
  selectedActivities: {{ json_encode(old('activities', [])) }},
  additionalItems: {{ json_encode(old('additional_items', [])) }},

  // Universal File Preview Modal State (Files remain hidden by default)
  formsDropdownOpen: false,
  previewModalOpen: false,
  previewModalTitle: '',
  previewModalUrl: '',
  previewModalType: 'image', // 'image' | 'pdf'

  openPreview(url, title, type = 'image') {
    this.previewModalUrl = url;
    this.previewModalTitle = title;
    this.previewModalType = type;
    this.previewModalOpen = true;
  },
  closePreview() {
    this.previewModalOpen = false;
    this.previewModalUrl = '';
    this.previewModalTitle = '';
  },

  // Student & Parent Photo State (Hidden by default, opened via View button)
  photoPreview: null,
  photoFileName: '',
  photoFileSize: '',
  handlePhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
      this.photoPreview = URL.createObjectURL(file);
      this.photoFileName = file.name;
      this.photoFileSize = (file.size / 1024 > 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB');
    }
  },
  clearPhoto() {
    this.photoPreview = null;
    this.photoFileName = '';
    this.photoFileSize = '';
    const input = document.getElementById('student_photo_input');
    if (input) input.value = '';
  },
  viewStudentPhoto() {
    if (this.photoPreview) {
      this.openPreview(this.photoPreview, 'Student Passport Photo — ' + (this.photoFileName || 'passport_photo.jpg'), 'image');
    }
  },

  fatherPhotoPreview: null,
  fatherPhotoFileName: '',
  fatherPhotoFileSize: '',
  handleFatherPhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
      this.fatherPhotoPreview = URL.createObjectURL(file);
      this.fatherPhotoFileName = file.name;
      this.fatherPhotoFileSize = (file.size / 1024 > 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB');
    }
  },
  clearFatherPhoto() {
    this.fatherPhotoPreview = null;
    this.fatherPhotoFileName = '';
    this.fatherPhotoFileSize = '';
    const input = document.getElementById('father_photo_input');
    if (input) input.value = '';
  },
  viewFatherPhoto() {
    if (this.fatherPhotoPreview) {
      this.openPreview(this.fatherPhotoPreview, 'Father Photo — ' + (this.fatherPhotoFileName || 'father_photo.jpg'), 'image');
    }
  },

  motherPhotoPreview: null,
  motherPhotoFileName: '',
  motherPhotoFileSize: '',
  handleMotherPhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
      this.motherPhotoPreview = URL.createObjectURL(file);
      this.motherPhotoFileName = file.name;
      this.motherPhotoFileSize = (file.size / 1024 > 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB');
    }
  },
  clearMotherPhoto() {
    this.motherPhotoPreview = null;
    this.motherPhotoFileName = '';
    this.motherPhotoFileSize = '';
    const input = document.getElementById('mother_photo_input');
    if (input) input.value = '';
  },
  viewMotherPhoto() {
    if (this.motherPhotoPreview) {
      this.openPreview(this.motherPhotoPreview, 'Mother Photo — ' + (this.motherPhotoFileName || 'mother_photo.jpg'), 'image');
    }
  },

  guardianPhotoPreview: null,
  guardianPhotoFileName: '',
  guardianPhotoFileSize: '',
  handleGuardianPhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
      this.guardianPhotoPreview = URL.createObjectURL(file);
      this.guardianPhotoFileName = file.name;
      this.guardianPhotoFileSize = (file.size / 1024 > 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB');
    }
  },
  clearGuardianPhoto() {
    this.guardianPhotoPreview = null;
    this.guardianPhotoFileName = '';
    this.guardianPhotoFileSize = '';
    const input = document.getElementById('guardian_photo_input');
    if (input) input.value = '';
  },
  viewGuardianPhoto() {
    if (this.guardianPhotoPreview) {
      this.openPreview(this.guardianPhotoPreview, 'Guardian Photo — ' + (this.guardianPhotoFileName || 'guardian_photo.jpg'), 'image');
    }
  },

  // Document Uploads State (Hidden by default, viewable via View button)
  uploadedDocs: {
    doc_birth_certificate: null,
    doc_aadhaar: null,
    doc_caste: null,
    doc_tc: null,
    doc_marksheet: null,
    doc_pan_id: null,
  },
  handleDocChange(event, key, label) {
    const file = event.target.files[0];
    if (file) {
      this.uploadedDocs[key] = {
        name: file.name,
        size: (file.size / 1024 > 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB'),
        type: file.type.includes('pdf') ? 'pdf' : 'image',
        url: URL.createObjectURL(file),
        label: label
      };
    } else {
      this.uploadedDocs[key] = null;
    }
  },
  viewDoc(key) {
    const doc = this.uploadedDocs[key];
    if (doc) {
      this.openPreview(doc.url, doc.label + ' — ' + doc.name, doc.type);
    }
  },
  clearDoc(key) {
    this.uploadedDocs[key] = null;
    const input = document.getElementById(key);
    if (input) input.value = '';
  },
  docUploadMode: 'desk',

  // Multi-Format Date of Birth State (Day, Month, Year selectors & DD/MM/YYYY text entry)
  dobMode: 'dropdown',
  dobDay: '{{ old("dob") ? date("d", strtotime(old("dob"))) : "" }}',
  dobMonth: '{{ old("dob") ? date("m", strtotime(old("dob"))) : "" }}',
  dobYear: '{{ old("dob") ? date("Y", strtotime(old("dob"))) : "" }}',
  dobText: '{{ old("dob") ? date("d/m/Y", strtotime(old("dob"))) : "" }}',
  handleDobTextInput(val) {
    let clean = val.replace(/\D/g, '').slice(0, 8);
    let formatted = clean;
    if (clean.length >= 3 && clean.length <= 4) {
      formatted = clean.slice(0, 2) + '/' + clean.slice(2);
    } else if (clean.length > 4) {
      formatted = clean.slice(0, 2) + '/' + clean.slice(2, 4) + '/' + clean.slice(4);
    }
    this.dobText = formatted;
    if (clean.length === 8) {
      this.dobDay = clean.slice(0, 2);
      this.dobMonth = clean.slice(2, 4);
      this.dobYear = clean.slice(4, 8);
    }
  },
  get computedDob() {
    if (this.dobDay && this.dobMonth && this.dobYear) {
      let d = String(this.dobDay).padStart(2, '0');
      let m = String(this.dobMonth).padStart(2, '0');
      let y = String(this.dobYear);
      return `${y}-${m}-${d}`;
    }
    return '';
  },
  get calculatedAge() {
    if (!this.computedDob) return null;
    let parts = this.computedDob.split('-');
    if (parts.length !== 3) return null;
    let birth = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (isNaN(birth.getTime())) return null;
    let now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    let months = now.getMonth() - birth.getMonth();
    if (months < 0 || (months === 0 && now.getDate() < birth.getDate())) {
      years--;
      months += 12;
    }
    if (years < 0) return null;
    return `${years} yrs ${months} mos`;
  },

  // Religion with Custom Options
  selectedReligion: '{{ in_array(old("religion"), ["Hindu", "Christian", "Muslim", "Jain", "Sikh", "Buddhist", "Parsi"]) ? old("religion") : (old("religion") ? "Other" : "") }}',
  customReligion: '{{ !in_array(old("religion"), ["Hindu", "Christian", "Muslim", "Jain", "Sikh", "Buddhist", "Parsi", ""]) ? old("religion") : "" }}',
  get effectiveReligion() {
    if (this.selectedReligion === 'Other') return this.customReligion;
    return this.selectedReligion;
  },

  // Mother Tongue with Custom Options
  selectedMotherTongue: '{{ in_array(old("mother_tongue"), ["Tamil", "English", "Hindi", "Telugu", "Malayalam", "Kannada", "Urdu", "Gujarati", "Marathi", "Bengali"]) ? old("mother_tongue") : (old("mother_tongue") ? "Other" : "") }}',
  customMotherTongue: '{{ !in_array(old("mother_tongue"), ["Tamil", "English", "Hindi", "Telugu", "Malayalam", "Kannada", "Urdu", "Gujarati", "Marathi", "Bengali", ""]) ? old("mother_tongue") : "" }}',
  get effectiveMotherTongue() {
    if (this.selectedMotherTongue === 'Other') return this.customMotherTongue;
    return this.selectedMotherTongue;
  },

  // Student Wearable Sizes (Dress & Shoe)
  selectedDressSize: '{{ old("dress_size", "") }}',
  customDressSize: '',
  get effectiveDressSize() {
    if (this.selectedDressSize === 'Other') return this.customDressSize;
    return this.selectedDressSize;
  },
  selectedShoeSize: '{{ old("shoe_size", "") }}',
  customShoeSize: '',
  get effectiveShoeSize() {
    if (this.selectedShoeSize === 'Other') return this.customShoeSize;
    return this.selectedShoeSize;
  },

  // Second Language Textbook & Curriculum Pack
  secondLanguage: '{{ old("second_language", "Tamil") }}',
  customSecondLanguage: '',
  get effectiveSecondLanguage() {
    if (this.secondLanguage === 'Other') return this.customSecondLanguage;
    return this.secondLanguage;
  },
  textbookPack: 'CBSE Standard NCERT Package',
  textbookNotes: '',

  // Custom Admission Kit & Textbooks Dynamic Items
  customKitItems: [],
  addCustomKitItem() {
    this.customKitItems.push({
      id: Date.now(),
      name: '',
      category: 'Textbook',
      specification: '',
      quantity: 1,
      unit_price: 0
    });
  },
  removeCustomKitItem(idx) {
    this.customKitItems.splice(idx, 1);
  },

  // Payment Terms & Account Destination State
  paymentTerms: '{{ old("payment_terms", "3_terms") }}',
  paymentMode: '{{ old("payment_mode", "UPI") }}',
  paymentAccount: '{{ old("payment_account", "upi") }}',
  upiRefNo: '{{ old("transaction_id", "") }}',
  setAccount(account, mode) {
    this.paymentAccount = account;
    this.paymentMode = mode;
    this.isPaymentVerified = false;
  },
  paymentDate: '{{ old("payment_date", date("Y-m-d")) }}',
  term2DueDate: '{{ old("term_2_due_date", "2026-08-05") }}',
  term3DueDate: '{{ old("term_3_due_date", "2026-12-05") }}',
  userAmountCollected: '{{ old("amount_collected", "") }}',
  userHasEditedAmountCollected: {{ old("amount_collected") !== null && old("amount_collected") !== '' ? 'true' : 'false' }},
  isIntegrated: {{ old("is_integrated") ? 'true' : 'false' }},
  includeAdmissionFee: {{ old("include_admission_fee", "1") == "1" ? 'true' : 'false' }},

  // Form Submission Lock
  isSubmitting: false,

  // Fee Payment Verification State
  isPaymentVerified: false,
  verificationStatus: 'Pending Verification',
  verifiedAt: '',
  verifiedBy: '',
  verificationNotes: '',
  verificationHistory: [],
  verificationModalOpen: false,

  verifyPayment() {
    let amt = this.amountCollected;
    if (this.userAmountCollected === '' || isNaN(amt) || amt < 0) {
      alert('Please enter a valid numerical payment amount before verifying.');
      return;
    }
    this.isPaymentVerified = true;
    this.verificationStatus = 'Verified';
    let now = new Date();
    this.verifiedAt = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ', ' + now.toLocaleDateString('en-IN');
    this.verifiedBy = '{{ Auth::user()?->name ?? "Admissions Officer" }}';
    this.verificationNotes = `Verified collection of ₹${amt.toLocaleString('en-IN')} via ${this.paymentMode} (${this.paymentAccount}).`;

    this.verificationHistory.unshift({
      timestamp: this.verifiedAt,
      amount: amt,
      formattedAmount: this.formatMoney(amt),
      mode: this.paymentMode,
      account: this.paymentAccount === 'cash_box_1' ? 'Front Office Cash (Box 1)' : (this.paymentAccount === 'cash_box_2' ? 'Accounts Vault (Box 2)' : 'UPI Digital Account'),
      status: 'Verified',
      verifier: this.verifiedBy,
      pending: this.formatMoney(this.pendingAmount),
      excess: this.formatMoney(this.excessAmount),
    });

    this.verificationModalOpen = true;
  },

  onAmountCollectedChanged(val) {
    this.userAmountCollected = val;
    this.userHasEditedAmountCollected = true;
    this.isPaymentVerified = false;
  },

  setAmountPreset(val) {
    this.userAmountCollected = String(val);
    this.userHasEditedAmountCollected = true;
    this.isPaymentVerified = false;
  },

  get availableSections() {
    if (!this.selectedClassId) return [];
    return this.sectionsList.filter(s => s.class_id == this.selectedClassId);
  },
  get selectedFee() {
    return this.standardFees[this.selectedClassId] || null;
  },
  get activeFeeSchedule() {
    if (!this.selectedFee) return null;
    if (this.isIntegrated && this.selectedFee.has_integrated) {
      return {
        term1_fee: Number(this.selectedFee.integrated_term1 || 52500),
        term2_fee: Number(this.selectedFee.integrated_term2 || 32500),
        term3_fee: Number(this.selectedFee.integrated_term3 || 20000),
        material_fee: 0,
        admission_fee: Number(this.selectedFee.admission_fee || 2500),
        total_basic: Number(this.selectedFee.integrated_fee || 105000),
        tier: this.selectedFee.class_name + ' (Integrated Coaching)',
        official_name: this.selectedFee.official_name + ' (INTEGRATED)',
        has_integrated: true
      };
    }
    return this.selectedFee;
  },
  get currentClassKit() {
    if (!this.selectedClassId || !this.admissionKitsData[this.selectedClassId]) return [];
    return this.admissionKitsData[this.selectedClassId];
  },
  get standardKitFeeTotal() {
    let sum = 0;
    this.currentClassKit.forEach(cfg => {
      let defaultQty = Number(cfg.default_quantity || 0);
      if (defaultQty > 0) {
        let price = Number(cfg.student_charge > 0 ? cfg.student_charge : (cfg.item?.student_price || 0));
        sum += defaultQty * price;
      }
    });
    return sum;
  },
  get additionalInventoryFeeTotal() {
    let sum = 0;
    this.currentClassKit.forEach(cfg => {
      let addQty = Number(this.additionalItems[cfg.item_id] || 0);
      if (addQty > 0) {
        let price = Number(cfg.student_charge > 0 ? cfg.student_charge : (cfg.item?.student_price || 0));
        sum += addQty * price;
      }
    });
    return sum;
  },
  get customKitFeeTotal() {
    return this.customKitItems.reduce((sum, item) => {
      let q = Number(item.quantity) || 0;
      let p = Number(item.unit_price) || 0;
      return sum + (q * p);
    }, 0);
  },
  get totalKitFee() {
    return this.standardKitFeeTotal + this.additionalInventoryFeeTotal + this.customKitFeeTotal;
  },
  get hasLowStockWarning() {
    return this.currentClassKit.some(cfg => {
      let addQty = Number(this.additionalItems[cfg.item_id] || 0);
      let totalQty = cfg.default_quantity + addQty;
      return (cfg.item?.current_stock || 0) < totalQty;
    });
  },
  get selectedClassName() {
    let c = this.classList.find(x => x.id == this.selectedClassId);
    return c ? c.name : '';
  },
  selectedStreamGroup: '{{ old("stream_group", "Group A: English, Mathematics, Physics, Chemistry, Biology / CS") }}',
  get classStage() {
    if (!this.selectedClassId) return '';
    let name = (this.selectedClassName || '').toUpperCase().trim();
    if (name.includes('PRE') || name.includes('LKG') || name.includes('UKG') || name.includes('KG')) {
      return 'kindergarten';
    }
    if (['XI', 'XII', '11', '12', 'GRADE XI', 'GRADE XII', 'CLASS XI', 'CLASS XII', '11TH', '12TH'].includes(name) || name.includes('11') || name.includes('12')) {
      return 'senior_secondary';
    }
    if (['IX', 'X', '9', '10', 'GRADE IX', 'GRADE X', 'CLASS IX', 'CLASS X', '9TH', '10TH'].includes(name)) {
      return 'secondary';
    }
    if (['VI', 'VII', 'VIII', '6', '7', '8', 'GRADE VI', 'GRADE VII', 'GRADE VIII'].includes(name)) {
      return 'middle';
    }
    if (['I', 'II', 'III', 'IV', 'V', '1', '2', '3', '4', '5', 'GRADE I', 'GRADE II', 'GRADE III', 'GRADE IV', 'GRADE V'].includes(name)) {
      return 'primary';
    }
    let numMatch = name.match(/\d+/);
    if (numMatch) {
      let n = parseInt(numMatch[0]);
      if (n === 11 || n === 12) return 'senior_secondary';
      if (n >= 9 && n <= 10) return 'secondary';
      if (n >= 6 && n <= 8) return 'middle';
      if (n >= 1 && n <= 5) return 'primary';
    }
    if (this.selectedFee && this.selectedFee.has_integrated) return 'senior_secondary';
    return 'general';
  },
  get isSeniorSecondary() {
    return this.classStage === 'senior_secondary' || (this.selectedFee && this.selectedFee.has_integrated);
  },
  get isSecondary() {
    return this.classStage === 'secondary';
  },
  get isMiddle() {
    return this.classStage === 'middle';
  },
  get isPrimary() {
    return this.classStage === 'primary';
  },
  get isKindergarten() {
    return this.classStage === 'kindergarten';
  },
  get basicFeeTotal() {
    return this.activeFeeSchedule ? (Number(this.activeFeeSchedule.total_basic) || 0) : 0;
  },
  get admissionFeeTotal() {
    if (!this.includeAdmissionFee || !this.activeFeeSchedule) return 0;
    return Number(this.activeFeeSchedule.admission_fee || 0);
  },
  get aspFeeTotal() {
    if (!this.aspRequired || !this.aspTerm) return 0;
    if (this.aspTerm === 'both') return Number(this.aspTermFee || 4000) * 2;
    return Number(this.aspTermFee || 4000);
  },
  get currentRoute() {
    if (!this.transportRequired || !this.selectedRouteId) return null;
    return this.transportRoutesData.find(r => r.id == this.selectedRouteId) || null;
  },
  get currentRouteVehicle() {
    return this.currentRoute?.vehicle || null;
  },
  get currentRouteStops() {
    return this.currentRoute?.stops || [];
  },
  get currentStop() {
    if (!this.currentRoute || !this.selectedStopId) return null;
    return (this.currentRoute.stops || []).find(s => s.id == this.selectedStopId) || null;
  },
  get currentDropStop() {
    if (!this.currentRoute) return null;
    let dropId = this.selectedDropStopId || this.selectedStopId;
    return (this.currentRoute.stops || []).find(s => s.id == dropId) || null;
  },
  get transportFee() {
    if (!this.transportRequired) return 0;
    if (this.currentStop && this.currentStop.fare !== null) return Number(this.currentStop.fare);
    if (this.currentRoute) return Number(this.currentRoute.fee || 0);
    return 0;
  },
  get transportDistanceKm() {
    if (!this.transportRequired) return 0;
    if (this.currentStop && this.currentStop.distance_km !== null) return Number(this.currentStop.distance_km);
    if (this.currentRoute) return Number(this.currentRoute.distance_km || 0);
    return 0;
  },
  get subTotal() {
    return this.basicFeeTotal + this.admissionFeeTotal + this.aspFeeTotal + this.transportFee + this.totalKitFee;
  },
  get calculatedConcession() {
    if (this.concessionType === 'none') return 0;
    if (this.concessionType === 'staff_kid') return Math.round(this.basicFeeTotal * 0.50);
    if (this.concessionType === 'single_shot') return Math.round(this.subTotal * 0.05);
    if (this.concessionType === 'topper') return Math.round(this.basicFeeTotal * 0.25);
    if (this.concessionType === 'sports') return Math.round(this.basicFeeTotal * 0.20);
    if (this.concessionType === 'sibling') return Math.round(this.basicFeeTotal * 0.15);
    return 0;
  },
  get concessionAmount() {
    if (this.concessionAmountInput !== '' && this.concessionAmountInput !== null) {
      let n = parseFloat(this.concessionAmountInput);
      return isNaN(n) ? 0 : Math.max(0, n);
    }
    return this.concessionType === 'none' ? 0 : this.calculatedConcession;
  },
  get grandTotal() {
    return Math.max(0, this.subTotal - this.concessionAmount);
  },
  get remainingFeesTotal() {
    return Math.max(0, this.grandTotal - this.totalKitFee);
  },

  // 2 TERMS SPLIT:
  // Term 1: 100% Kit + 50% Term Fee + 50% Transport
  // Term 2: 50% Term Fee + 50% Transport
  get twoTermsT1() {
    let tFee = this.transportFee;
    let transportT1 = Math.round(tFee * 0.5);
    let termFeeBase = Math.max(0, this.grandTotal - this.totalKitFee - tFee);
    let termFeeT1 = Math.round(termFeeBase * 0.5);
    return Math.max(0, this.totalKitFee + termFeeT1 + transportT1);
  },
  get twoTermsT2() {
    return Math.max(0, this.grandTotal - this.twoTermsT1);
  },

  // 3 TERMS SPLIT:
  // Term 1: 100% Kit + T1 Fee + 50% Transport
  // Term 2: T2 Fee + 50% Transport
  // Term 3: T3 Fee (0% Transport)
  get threeTermsT1() {
    let tFee = this.transportFee;
    let transportT1 = Math.round(tFee * 0.5);
    let baseT1 = Number(this.activeFeeSchedule?.term1_fee || 0) + this.admissionFeeTotal + (Number(this.activeFeeSchedule?.material_fee || 0));
    let aspShare = Math.round(this.aspFeeTotal / 3);
    let discShare = Math.round(this.concessionAmount / 3);
    return Math.max(0, this.totalKitFee + baseT1 + transportT1 + aspShare - discShare);
  },
  get threeTermsT2() {
    let tFee = this.transportFee;
    let transportT2 = tFee - Math.round(tFee * 0.5);
    let baseT2 = Number(this.activeFeeSchedule?.term2_fee || 0);
    let aspShare = Math.round(this.aspFeeTotal / 3);
    let discShare = Math.round(this.concessionAmount / 3);
    return Math.max(0, baseT2 + transportT2 + aspShare - discShare);
  },
  get threeTermsT3() {
    return Math.max(0, this.grandTotal - (this.threeTermsT1 + this.threeTermsT2));
  },

  // Active Term amounts calculations (used in schedule breakdown table and form submission)
  get term1Amount() {
    if (this.paymentTerms === 'single') return this.grandTotal;
    if (this.paymentTerms === '2_terms') return this.twoTermsT1;
    if (this.paymentTerms === '3_terms') return this.threeTermsT1;
    return this.grandTotal;
  },
  get term2Amount() {
    if (this.paymentTerms === 'single') return 0;
    if (this.paymentTerms === '2_terms') return this.twoTermsT2;
    if (this.paymentTerms === '3_terms') return this.threeTermsT2;
    return 0;
  },
  get term3Amount() {
    if (this.paymentTerms === '3_terms') return this.threeTermsT3;
    return 0;
  },

  get amountCollected() {
    if (this.userAmountCollected !== null && this.userAmountCollected !== '') {
      let n = parseFloat(this.userAmountCollected);
      return isNaN(n) ? 0 : Math.max(0, n);
    }
    return this.term1Amount;
  },
  get pendingAmount() {
    return Math.max(0, this.grandTotal - this.amountCollected);
  },
  get excessAmount() {
    return Math.max(0, this.amountCollected - this.grandTotal);
  },
  get paymentStatus() {
    if (this.amountCollected >= this.grandTotal && this.grandTotal > 0) return 'Paid';
    if (this.amountCollected > 0) return 'Partially Paid';
    return 'Pending';
  },

  formatMoney(num) {
    return '₹' + Number(num || 0).toLocaleString('en-IN');
  }
}">

  {{-- ── Top Navigation / Title Header ────────────────────────── --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
    <div class="flex items-center gap-3">
      <a href="{{ route('admissions.index') }}" class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-white hover:bg-slate-100 text-slate-700 transition border border-slate-200/90 shadow-2xs group" title="Back to Admissions">
        <svg class="w-4 h-4 text-slate-600 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">New Student Admission Form</h1>
        <p class="text-xs text-slate-500 font-medium flex flex-wrap items-center gap-1.5 mt-0.5">
          <span class="font-semibold text-slate-700">Erode Public School</span>
          <span>&bull;</span>
          <span class="text-slate-500">CBSE Affiliated 1931585</span>
          <span>&bull;</span>
          <span class="text-[#8C2826] font-bold bg-[#FFF5F5] px-2.5 py-0.5 rounded-lg border border-[#FECACA]">Academic Year: {{ $academicYear?->name ?? '2026-2027' }}</span>
        </p>
      </div>
    </div>

    {{-- Consolidated Official Blank Application Forms Dropdown (PDF 1, 2, 3) --}}
    <div class="relative" @click.away="formsDropdownOpen = false">
      <button type="button" @click="formsDropdownOpen = !formsDropdownOpen"
              class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 text-xs font-bold shadow-2xs hover:border-slate-300 transition cursor-pointer">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>Print Blank Forms</span>
        <svg class="w-3.5 h-3.5 text-slate-400 transition transform" :class="formsDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>

      <div x-show="formsDropdownOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
           class="absolute right-0 mt-2 w-64 rounded-2xl bg-white border border-slate-200/90 shadow-xl p-1.5 z-40 space-y-1">
        <a href="{{ route('admissions.print-form', ['form' => 'admission']) }}" target="_blank"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-700 transition group">
          <span class="w-6 h-6 rounded-lg bg-[#FFF5F5] flex items-center justify-center group-hover:bg-[#8C2826] transition">
            <svg class="w-3.5 h-3.5 text-[#8C2826] group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </span>
          <div>
            <div class="font-bold text-slate-900">General Admission Form</div>
            <div class="text-[10px] text-slate-400 font-normal">Pre-KG to Class X (PDF 1)</div>
          </div>
        </a>
        <a href="{{ route('admissions.print-form', ['form' => 'grade11']) }}" target="_blank"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-700 transition group">
          <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center group-hover:bg-[#8C2826] transition">
            <svg class="w-3.5 h-3.5 text-indigo-600 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
          </span>
          <div>
            <div class="font-bold text-slate-900">Grade XI Application Form</div>
            <div class="text-[10px] text-slate-400 font-normal">Streams &amp; Board Norms (PDF 2)</div>
          </div>
        </a>
        <a href="{{ route('admissions.print-form', ['form' => 'enquiry']) }}" target="_blank"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-700 transition group">
          <span class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center group-hover:bg-[#C8973A] transition">
            <svg class="w-3.5 h-3.5 text-emerald-600 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </span>
          <div>
            <div class="font-bold text-slate-900">Walk-in Enquiry Form</div>
            <div class="text-[10px] text-slate-400 font-normal">Counseling &amp; Visit Log (PDF 3)</div>
          </div>
        </a>
      </div>
    </div>
  </div>

  {{-- Print Header --}}
  <div class="hidden print:block border-b-2 border-red-700 pb-3 mb-6">
    <h1 class="text-3xl font-black text-red-700 tracking-wider uppercase font-school-title">ERODE PUBLIC SCHOOL</h1>
    <p class="text-xs font-bold text-slate-800 uppercase tracking-widest mt-0.5">Senior Secondary Affiliated to CBSE, New Delhi (Aff.No.1931585)</p>
    <p class="text-xs text-slate-600 font-medium mt-0.5">Academic Year: {{ $academicYear?->name ?? '2026-2027' }} &bull; Date: {{ date('d-m-Y') }}</p>
  </div>

  {{-- Modern Section Navigator --}}
  <div class="bg-white/95 backdrop-blur-md px-3 py-2 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-2 overflow-x-auto print:hidden sticky top-3 z-30">
    <a href="#section-student" class="flex-1 min-w-[150px] flex items-center gap-2.5 px-3.5 py-2 rounded-xl hover:bg-[#FFF5F5] transition group border border-transparent hover:border-[#FECACA]">
      <span class="w-7 h-7 rounded-xl bg-[#8C2826] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">1</span>
      <div class="min-w-0">
        <span class="font-bold text-slate-800 group-hover:text-[#8C2826] text-xs block truncate transition">Student &amp; Academic</span>
        <span class="text-[10px] text-slate-400 block truncate">Profile &amp; Placement</span>
      </div>
    </a>

    <div class="h-4 w-px bg-slate-200 shrink-0"></div>

    <a href="#section-parents" class="flex-1 min-w-[150px] flex items-center gap-2.5 px-3.5 py-2 rounded-xl hover:bg-[#FFF5F5] transition group border border-transparent hover:border-[#FECACA]">
      <span class="w-7 h-7 rounded-xl bg-indigo-100 text-indigo-700 group-hover:bg-[#8C2826] group-hover:text-white font-bold text-xs flex items-center justify-center shrink-0 transition">2</span>
      <div class="min-w-0">
        <span class="font-bold text-slate-700 group-hover:text-[#8C2826] text-xs block truncate transition">Parents &amp; Facilities</span>
        <span class="text-[10px] text-slate-400 block truncate">Family, Bus &amp; Programs</span>
      </div>
    </a>

    <div class="h-4 w-px bg-slate-200 shrink-0"></div>

    <a href="#section-billing" class="flex-1 min-w-[150px] flex items-center gap-2.5 px-3.5 py-2 rounded-xl hover:bg-[#FEF9EE] transition group border border-transparent hover:border-[#FDE68A]">
      <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 group-hover:bg-[#C8973A] group-hover:text-white font-bold text-xs flex items-center justify-center shrink-0 transition">3</span>
      <div class="min-w-0">
        <span class="font-bold text-slate-700 group-hover:text-[#C8973A] text-xs block truncate transition">Kits, Fees &amp; Billing</span>
        <span class="text-[10px] text-slate-400 block truncate">Package &amp; Payment</span>
      </div>
    </a>
  </div>

  <form id="admissionCreateForm" method="POST" action="{{ route('admissions.store') }}" enctype="multipart/form-data" class="space-y-10" novalidate>
    @csrf

    {{-- Idempotency Token and Payment Verification Hidden State --}}
    <input type="hidden" name="submission_token" id="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
    <input type="hidden" name="payment_verified" id="payment_verified" :value="isPaymentVerified ? '1' : '0'">
    <input type="hidden" name="payment_verified_at" id="payment_verified_at" :value="verifiedAt">
    <input type="hidden" name="payment_verified_by" id="payment_verified_by" :value="verifiedBy">
    <input type="hidden" name="payment_verification_notes" id="payment_verification_notes" :value="verificationNotes">

    {{-- Auto-Draft Recovery Notice Banner --}}
    <div id="draftRecoveryBanner" class="hidden p-4 sm:p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-950 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start sm:items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <h4 class="text-xs sm:text-sm font-extrabold text-amber-900 flex items-center gap-2">
            <span>Unsaved Admission Draft Restored</span>
            <span class="text-[10px] font-bold bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full">Auto-Saved</span>
          </h4>
          <p class="text-[11px] sm:text-xs text-amber-800 mt-0.5" id="draftTimestampText">Your previously entered form inputs have been preserved. You can continue editing or start fresh.</p>
        </div>
      </div>
      <div class="flex items-center gap-2.5 self-end sm:self-auto shrink-0">
        <button type="button" onclick="document.getElementById('draftRecoveryBanner').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shadow-2xs cursor-pointer">
          Keep Draft Data
        </button>
        <button type="button" onclick="discardDraftAndReset()" class="px-3.5 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-700 hover:text-rose-800 border border-amber-200 hover:border-rose-300 text-xs font-bold transition shadow-2xs cursor-pointer">
          Discard &amp; Start Fresh
        </button>
      </div>
    </div>

    {{-- Server Validation Error Banner --}}
    @if ($errors->any())
      <div id="serverValidationAlert" class="p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900 shadow-md flex items-start gap-4">
        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-extrabold text-sm text-rose-900">Please review and fix the following ({{ $errors->count() }}) errors:</h4>
          <ul class="list-disc list-inside text-xs mt-1.5 space-y-1 font-medium text-rose-800">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-700 p-1 transition" title="Dismiss">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    @endif

    {{-- Client Validation Error Banner --}}
    <div id="clientValidationAlert" class="hidden p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900 shadow-md flex items-start gap-4">
      <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="font-extrabold text-sm text-rose-900" id="clientValidationTitle">Please correct the highlighted fields before submitting:</h4>
        <ul id="clientValidationList" class="list-disc list-inside text-xs mt-1.5 space-y-1 font-medium text-rose-800"></ul>
      </div>
      <button type="button" onclick="document.getElementById('clientValidationAlert').classList.add('hidden')" class="text-rose-400 hover:text-rose-700 p-1 transition" title="Dismiss">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

        {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── STEP 1: STUDENT PROFILE & ACADEMIC ADMISSION ──────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="section-student" class="bg-white p-7 sm:p-10 lg:p-12 rounded-3xl border border-slate-200/90 shadow-sm space-y-9 print-card scroll-mt-24">
      {{-- Step Header --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#8C2826] to-[#A83230] text-white flex items-center justify-center font-black text-base shadow-sm ring-4 ring-[#8C2826]/10">1</span>
            <div>
              <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Student Profile &amp; Academic Placement</h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Personal identity, vital records, curriculum allocation, and official government registration identifiers.</p>
            </div>
          </div>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FFF5F5] border border-[#FECACA] shadow-2xs self-start sm:self-auto">
          <span class="w-2 h-2 rounded-full bg-[#8C2826] animate-pulse"></span>
          <span class="text-xs font-extrabold text-[#8C2826] tracking-wide">Step 1 of 3</span>
        </div>
      </div>

      {{-- Section 1.1: Student Legal Identity & Photograph Card --}}
      <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/90 shadow-2xs space-y-6">
        {{-- Section Subhead --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA] flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Student Legal Identity &amp; Photograph</h3>
              <p class="text-[11px] text-slate-500 font-medium">Official student identity as documented in birth certificate or Aadhaar</p>
            </div>
          </div>
          <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500 bg-slate-50 px-2.5 py-1 rounded-full border border-slate-200/80 self-start sm:self-auto">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Official Record
          </span>
        </div>

        {{-- Row 1: Student Passport Photo Uploader Bar --}}
        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 flex flex-col sm:flex-row items-center gap-5">
          {{-- Passport Photo Preview Frame (Hidden by default, opened via View Photo button) --}}
          <div class="relative shrink-0 cursor-pointer group" @click="$refs.studentPhotoInput.click()">
            <div class="w-24 h-28 rounded-2xl bg-white border-2 border-dashed border-slate-300 group-hover:border-[#8C2826] flex items-center justify-center overflow-hidden shadow-xs transition-all duration-200">
              <template x-if="photoPreview">
                <div class="w-full h-full bg-emerald-50/70 border border-emerald-200 rounded-2xl flex flex-col items-center justify-center text-center p-2.5">
                  <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm mb-1.5 shadow-2xs">
                    ✓
                  </div>
                  <span class="text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider block">Attached</span>
                  <span class="text-[9px] text-emerald-700 font-medium">Click View</span>
                </div>
              </template>
              <template x-if="!photoPreview">
                <div class="text-center p-2 text-slate-400 group-hover:text-[#8C2826] transition-colors">
                  <svg class="w-7 h-7 mx-auto text-slate-400 group-hover:text-[#8C2826] mb-1 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="1.75"/></svg>
                  <span class="text-[10px] font-bold block uppercase tracking-wider">Photo</span>
                </div>
              </template>
            </div>

            {{-- Remove Button if Uploaded --}}
            <template x-if="photoPreview">
              <button type="button" @click.stop="clearPhoto()" title="Remove photo"
                      class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-md transition cursor-pointer z-10">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </template>
          </div>

          {{-- Guidance & Actions --}}
          <div class="flex-1 min-w-0 text-center sm:text-left space-y-2">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wide">Student Passport Photograph</label>
              <span class="text-[10px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">Optional at Desk</span>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">Upload a clear formal color passport photograph against a plain background (JPEG, PNG &bull; max 3MB). Stored securely and kept hidden on form by default.</p>
            
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
              <button type="button" @click="$refs.studentPhotoInput.click()"
                      class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-800 font-bold text-xs border border-slate-200 shadow-2xs hover:border-[#8C2826] transition cursor-pointer">
                <svg class="w-4 h-4 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-text="photoPreview ? 'Change Photo' : 'Upload Photograph'"></span>
              </button>

              {{-- Explicit View Button --}}
              <template x-if="photoPreview">
                <button type="button" @click="viewStudentPhoto()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs border border-indigo-200 shadow-2xs transition cursor-pointer">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span>View Photo</span>
                </button>
              </template>

              <template x-if="photoFileName">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-medium shadow-2xs max-w-[280px]">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                  <span class="truncate" x-text="photoFileName"></span>
                  <span class="text-[10px] text-slate-400 font-semibold" x-text="photoFileSize"></span>
                  <button type="button" @click="clearPhoto()" class="text-slate-400 hover:text-rose-600 ml-1 cursor-pointer transition" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </template>
            </div>
            @error('photo') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
          </div>

          {{-- Hidden Native File Input --}}
          <input type="file" id="student_photo_input" x-ref="studentPhotoInput" name="photo" accept="image/*"
                 @change="handlePhotoChange($event)" class="hidden">
        </div>

        {{-- Row 2: Student Full Legal Names (Balanced 2-Column Grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-1">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="first_name" class="block text-xs font-bold text-slate-800">
                First / Given Name <span class="text-rose-500">*</span>
              </label>
              <span class="text-[10px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">Required</span>
            </div>
            <div class="relative group/input">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within/input:text-[#8C2826] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <input type="text" id="first_name" name="first_name" required maxlength="50" value="{{ old('first_name') }}"
                     placeholder="Student first name (e.g. Akash)"
                     class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal hover:border-slate-300 focus:border-[#8C2826] focus:ring-4 focus:ring-[#8C2826]/10 transition shadow-2xs outline-none">
            </div>
            @error('first_name') <p class="text-xs text-rose-500 mt-1.5 font-semibold">{{ $message }}</p> @enderror
            <p id="first_name_client_err" class="text-xs text-rose-500 mt-1.5 font-semibold hidden"></p>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="last_name" class="block text-xs font-bold text-slate-800">
                Last Name / Surname / Initial
              </label>
              <span class="text-[10px] font-medium text-slate-400">Optional</span>
            </div>
            <div class="relative group/input">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within/input:text-[#8C2826] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <input type="text" id="last_name" name="last_name" maxlength="50" value="{{ old('last_name') }}"
                     placeholder="Surname or initial (e.g. A R)"
                     class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal hover:border-slate-300 focus:border-[#8C2826] focus:ring-4 focus:ring-[#8C2826]/10 transition shadow-2xs outline-none">
            </div>
            @error('last_name') <p class="text-xs text-rose-500 mt-1.5 font-semibold">{{ $message }}</p> @enderror
            <p id="last_name_client_err" class="text-xs text-rose-500 mt-1.5 font-semibold hidden"></p>
          </div>
        </div>

        {{-- Row 3: Guidance Note Banner --}}
        <div class="flex items-start gap-2.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-slate-600 text-xs font-medium leading-relaxed">
          <svg class="w-4 h-4 text-[#8C2826] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Please enter student name exactly as registered in official Municipal Birth Certificate or Aadhaar Card. Used for CBSE register, permanent transfer certificates, and grade cards.</span>
        </div>
      </div>

      {{-- Section 1.2: Date of Birth & Demographic Profile (Spacious 3-Column Grids) --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-rose-50 text-[#8C2826] border border-rose-100 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Date of Birth &amp; Demographic Profile</h3>
              <p class="text-[11px] text-slate-500 font-medium">Vital statistics, computed admission age, and statutory community category</p>
            </div>
          </div>
          <div class="hidden sm:block h-px bg-slate-100 flex-1 ml-4"></div>
        </div>

        {{-- Row 1: DOB, Gender, Blood Group --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          {{-- Date of Birth (with Mode Switch & Age Pill) --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-700">Date of Birth <span class="text-rose-500">*</span></label>
              <div class="flex items-center gap-1.5">
                <span x-show="calculatedAge" class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-2 py-0.5 rounded-full border border-[#FECACA]" x-text="'Age: ' + calculatedAge"></span>
                <button type="button" @click="dobMode = (dobMode === 'dropdown' ? 'text' : 'dropdown')"
                        class="inline-flex items-center gap-1 text-[10px] text-[#8C2826] hover:text-[#731E1C] font-bold cursor-pointer bg-[#FFF5F5] hover:bg-rose-100/60 px-2 py-0.5 rounded-md border border-[#FECACA] transition">
                  <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                  <span x-text="dobMode === 'dropdown' ? 'Type Date' : 'Selectors'"></span>
                </button>
              </div>
            </div>

            {{-- Mode 1: 3 Clean Selectors (Day, Month, Year) --}}
            <div x-show="dobMode === 'dropdown'" class="grid grid-cols-3 gap-1.5">
              <select x-model="dobDay"
                      class="w-full px-2 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="">Day</option>
                @for($d = 1; $d <= 31; $d++)
                  @php $dStr = str_pad($d, 2, '0', STR_PAD_LEFT); @endphp
                  <option value="{{ $dStr }}">{{ $dStr }}</option>
                @endfor
              </select>

              <select x-model="dobMonth"
                      class="w-full px-2 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="">Month</option>
                @foreach([
                  '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                  '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                  '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                ] as $mNum => $mName)
                  <option value="{{ $mNum }}">{{ $mName }}</option>
                @endforeach
              </select>

              <select x-model="dobYear"
                      class="w-full px-2 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="">Year</option>
                @for($y = 2025; $y >= 2004; $y--)
                  <option value="{{ $y }}">{{ $y }}</option>
                @endfor
              </select>
            </div>

            {{-- Mode 2: Direct Text Input (DD/MM/YYYY) --}}
            <div x-show="dobMode === 'text'" class="relative">
              <input type="text" :value="dobText" @input="handleDobTextInput($event.target.value)"
                     placeholder="DD/MM/YYYY (e.g. 15/08/2018)" maxlength="10"
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition bg-white">
            </div>

            {{-- Hidden input for server submission --}}
            <input type="hidden" id="dob" name="dob" :value="computedDob">
            @error('dob') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="dob_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          {{-- Gender --}}
          <div>
            <label for="gender" class="block text-xs font-bold text-slate-700 mb-2">Gender <span class="text-rose-500">*</span></label>
            <select id="gender" name="gender" x-model="selectedGender" @change="fetchBooksNotesChecklist()" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">Select gender</option>
              <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
              <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
              <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="gender_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          {{-- Blood Group --}}
          <div>
            <label for="blood_group" class="block text-xs font-bold text-slate-700 mb-2">Blood Group</label>
            <select id="blood_group" name="blood_group" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">Select Blood Group</option>
              <option value="A+" {{ old('blood_group') === 'A+' ? 'selected' : '' }}>A+</option>
              <option value="A-" {{ old('blood_group') === 'A-' ? 'selected' : '' }}>A-</option>
              <option value="B+" {{ old('blood_group') === 'B+' ? 'selected' : '' }}>B+</option>
              <option value="B-" {{ old('blood_group') === 'B-' ? 'selected' : '' }}>B-</option>
              <option value="O+" {{ old('blood_group') === 'O+' ? 'selected' : '' }}>O+</option>
              <option value="O-" {{ old('blood_group') === 'O-' ? 'selected' : '' }}>O-</option>
              <option value="AB+" {{ old('blood_group') === 'AB+' ? 'selected' : '' }}>AB+</option>
              <option value="AB-" {{ old('blood_group') === 'AB-' ? 'selected' : '' }}>AB-</option>
            </select>
          </div>
        </div>

        {{-- Row 2: Religion, Category, Mother Tongue --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          {{-- Religion --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-700">Religion</label>
              <span x-show="selectedReligion === 'Other'" class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-1.5 py-0.5 rounded">Custom</span>
            </div>
            <select x-model="selectedReligion"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">-- Select Religion --</option>
              <option value="Hindu">Hindu</option>
              <option value="Christian">Christian</option>
              <option value="Muslim">Muslim</option>
              <option value="Jain">Jain</option>
              <option value="Sikh">Sikh</option>
              <option value="Buddhist">Buddhist</option>
              <option value="Parsi">Parsi</option>
              <option value="Other">Other (Custom Religion)</option>
            </select>
            <div x-show="selectedReligion === 'Other'" x-transition class="mt-2">
              <input type="text" x-model="customReligion" placeholder="Type custom religion..."
                     class="w-full px-4 py-2.5 rounded-xl border border-[#FECACA] focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium bg-[#FFF5F5]">
            </div>
            <input type="hidden" name="religion" :value="effectiveReligion">
          </div>

          {{-- Caste / Community Category --}}
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Community Category</label>
            <select name="category" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">Select Category</option>
              <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General</option>
              <option value="obc" {{ old('category') === 'obc' ? 'selected' : '' }}>OBC</option>
              <option value="sc" {{ old('category') === 'sc' ? 'selected' : '' }}>SC</option>
              <option value="st" {{ old('category') === 'st' ? 'selected' : '' }}>ST</option>
              <option value="ews" {{ old('category') === 'ews' ? 'selected' : '' }}>EWS</option>
              <option value="minority" {{ old('category') === 'minority' ? 'selected' : '' }}>Minority</option>
            </select>
          </div>

          {{-- Mother Tongue --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-700">Mother Tongue</label>
              <span x-show="selectedMotherTongue === 'Other'" class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-1.5 py-0.5 rounded">Custom</span>
            </div>
            <select x-model="selectedMotherTongue"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">-- Select Mother Tongue --</option>
              <option value="Tamil">Tamil</option>
              <option value="English">English</option>
              <option value="Hindi">Hindi</option>
              <option value="Telugu">Telugu</option>
              <option value="Malayalam">Malayalam</option>
              <option value="Kannada">Kannada</option>
              <option value="Urdu">Urdu</option>
              <option value="Gujarati">Gujarati</option>
              <option value="Marathi">Marathi</option>
              <option value="Bengali">Bengali</option>
              <option value="Other">Other (Custom Language)</option>
            </select>
            <div x-show="selectedMotherTongue === 'Other'" x-transition class="mt-2">
              <input type="text" x-model="customMotherTongue" placeholder="Type custom mother tongue..."
                     class="w-full px-4 py-2.5 rounded-xl border border-[#FECACA] focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium bg-[#FFF5F5]">
            </div>
            <input type="hidden" name="mother_tongue" :value="effectiveMotherTongue">
          </div>
        </div>
      </div>

      {{-- Section 1.3: Class Enrollment & Stage Selection --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Class Enrollment &amp; Academic Stage</h3>
              <p class="text-[11px] text-slate-500 font-medium">Academic year, standard selection, and stage curriculum</p>
            </div>
          </div>
          <div class="hidden sm:block h-px bg-slate-100 flex-1 ml-4"></div>
        </div>

        {{-- Academic Year, Class & Enquiry Source (Spacious 3-Column Grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div>
            <label for="academic_year_id" class="block text-xs font-bold text-slate-700 mb-2">Academic Year <span class="text-rose-500">*</span></label>
            <select id="academic_year_id" name="academic_year_id" required x-model="academicYearId"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-bold text-slate-900 bg-white">
              <template x-for="y in academicYearsList" :key="y.id">
                <option :value="y.id" :selected="y.id == academicYearId" x-text="y.name + (y.is_current ? ' (Current)' : '')"></option>
              </template>
            </select>
          </div>

          <div>
            <label for="class_id" class="block text-xs font-bold text-slate-700 mb-2">Applying for Standard / Class <span class="text-rose-500">*</span></label>
            <select id="class_id" name="class_id" required x-model="selectedClassId"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-bold text-slate-900 bg-white">
              <option value="">-- Select Class (Pre-KG to 12) --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}">Class {{ $c->name }}</option>
              @endforeach
            </select>
            @error('class_id') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="class_id_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Enquiry / Application Source</label>
            <select name="source" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="Walk-in">Walk-in Visit</option>
              <option value="Online">Online / School Website</option>
              <option value="Referral">Parent Referral</option>
              <option value="Advertisement">Newspaper / Social Media</option>
            </select>
          </div>
        </div>

        {{-- Live Approved Fee Schedule Card for Selected Class --}}
        <div x-show="selectedClassId && selectedFee" x-transition class="p-6 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-[#8C2826] text-white flex items-center justify-center font-black text-sm shadow-xs">₹</span>
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-slate-900 uppercase tracking-wide" x-text="'Approved Academic Fee Schedule &mdash; ' + (selectedFee?.official_name || ('Class ' + selectedClassName))"></span>
                  <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA]">2026–2027</span>
                </div>
                <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Official term-wise tuition &amp; examination fee breakdown</span>
              </div>
            </div>

            {{-- Integrated Coaching Toggle for Grade XI / XII --}}
            <div x-show="selectedFee?.has_integrated" class="flex items-center gap-1 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs">
              <button type="button" @click="isIntegrated = false"
                      :class="!isIntegrated ? 'bg-[#8C2826] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                      class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                Standard (₹80,000)
              </button>
              <button type="button" @click="isIntegrated = true"
                      :class="isIntegrated ? 'bg-[#8C2826] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                      class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                <span>Integrated (₹1,05,000)</span>
                <span class="text-[9px] bg-amber-400 text-slate-950 font-black px-1.5 py-0.5 rounded">NEET/JEE</span>
              </button>
            </div>
          </div>

          {{-- 4 Term Breakdown Stat Cards --}}
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 text-xs">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">April &bull; Term I</span>
              <span class="font-extrabold text-slate-900 text-sm tabular-nums mt-0.5 block" x-text="formatMoney(activeFeeSchedule?.term1_fee)"></span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">August &bull; Term II</span>
              <span class="font-extrabold text-slate-900 text-sm tabular-nums mt-0.5 block" x-text="formatMoney(activeFeeSchedule?.term2_fee)"></span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
              <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">December &bull; Term III</span>
              <span class="font-extrabold text-slate-900 text-sm tabular-nums mt-0.5 block" x-text="formatMoney(activeFeeSchedule?.term3_fee)"></span>
            </div>
            <div class="bg-[#8C2826] text-white p-3.5 rounded-xl shadow-xs">
              <span class="text-[10px] uppercase font-bold text-amber-200 block tracking-wider">Total Academic Fee</span>
              <span class="font-black text-white text-sm tabular-nums mt-0.5 block" x-text="formatMoney(activeFeeSchedule?.total_basic)"></span>
            </div>
          </div>

          {{-- Notes on Admission & Material Fee --}}
          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200/70 text-[11px] text-slate-600">
            <div class="flex items-center gap-3">
              <span class="font-bold text-[#8C2826]" x-show="activeFeeSchedule?.admission_fee > 0">
                &bull; Admission Fee: ₹2,500/- for new student enrollment
              </span>
              <span class="font-bold text-slate-700" x-show="activeFeeSchedule?.material_fee > 0">
                &bull; Material Package: ₹19,500/- (Class X Board Prep)
              </span>
            </div>
            <span class="text-slate-400 font-medium text-[10px]">
              Due Dates: T1: 01.04.2026 &bull; T2: 05.08.2026 &bull; T3: 05.12.2026
            </span>
          </div>
        </div>

        {{-- Hidden Inputs for Server Submission --}}
        <input type="hidden" name="is_integrated" :value="isIntegrated ? 1 : 0">
        <input type="hidden" name="include_admission_fee" :value="includeAdmissionFee ? 1 : 0">
        <input type="hidden" name="admission_fee_amount" :value="admissionFeeTotal">

        {{-- ── Academic Stage Subsection 1: Senior Secondary Stream (Grade XI & XII) ── --}}
        <div x-show="isSeniorSecondary" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="p-6 rounded-2xl border border-slate-200/90 bg-slate-50/50 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wide">Senior Secondary Stream &amp; Curriculum (Class XI &amp; XII)</span>
                <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-2.5 py-0.5 rounded border border-[#FECACA]">Official CBSE</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">CBSE Senior Secondary specialization stream selection for Class XI / XII admission</p>
            </div>
            <a href="{{ route('admissions.print-form', ['form' => 'grade11']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 shadow-2xs transition self-start sm:self-auto" title="Print Grade XI Form">
              <svg class="w-3.5 h-3.5 text-[#8C2826]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              <span>Grade XI Form</span>
            </a>
          </div>

          {{-- The 4 Stream Options with interactive radio cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            {{-- Group A --}}
            <label class="p-4 rounded-xl border transition flex items-start gap-3 cursor-pointer bg-white"
                   :class="selectedStreamGroup.includes('Group A') ? 'border-[#8C2826] ring-2 ring-[#8C2826]/15 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
              <input type="radio" name="stream_group" x-model="selectedStreamGroup" value="Group A: English, Mathematics, Physics, Chemistry, Biology / CS" class="mt-1 text-[#8C2826] focus:ring-[#8C2826]">
              <div class="space-y-1">
                <div class="flex items-center gap-1.5">
                  <span class="font-extrabold text-slate-900 text-xs">Science Stream (Group A)</span>
                  <span class="text-[9px] font-bold bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA] px-2 py-0.5 rounded tracking-wide">ENGG / RESEARCH</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">English, Mathematics, Physics, Chemistry, Biology / Computer Science</p>
              </div>
            </label>

            {{-- Group B --}}
            <label class="p-4 rounded-xl border transition flex items-start gap-3 cursor-pointer bg-white"
                   :class="selectedStreamGroup.includes('Group B') ? 'border-[#8C2826] ring-2 ring-[#8C2826]/15 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
              <input type="radio" name="stream_group" x-model="selectedStreamGroup" value="Group B: English, Physics, Chemistry, Biology, Mathematics / CS" class="mt-1 text-[#8C2826] focus:ring-[#8C2826]">
              <div class="space-y-1">
                <div class="flex items-center gap-1.5">
                  <span class="font-extrabold text-slate-900 text-xs">Science Stream (Group B)</span>
                  <span class="text-[9px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded tracking-wide">NEET / MEDICAL</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">English, Physics, Chemistry, Biology, Mathematics / Computer Science</p>
              </div>
            </label>

            {{-- Group C --}}
            <label class="p-4 rounded-xl border transition flex items-start gap-3 cursor-pointer bg-white"
                   :class="selectedStreamGroup.includes('Group C') ? 'border-[#8C2826] ring-2 ring-[#8C2826]/15 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
              <input type="radio" name="stream_group" x-model="selectedStreamGroup" value="Group C: English, Business Studies / Entr, Accountancy, Economics, CS" class="mt-1 text-[#8C2826] focus:ring-[#8C2826]">
              <div class="space-y-1">
                <div class="flex items-center gap-1.5">
                  <span class="font-extrabold text-slate-900 text-xs">Commerce Stream (Group C)</span>
                  <span class="text-[9px] font-bold bg-amber-50 text-amber-900 border border-amber-200 px-2 py-0.5 rounded tracking-wide">COMMERCE / CA</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">English, Business Studies / Entr, Accountancy, Economics, Computer Science</p>
              </div>
            </label>

            {{-- Group D --}}
            <label class="p-4 rounded-xl border transition flex items-start gap-3 cursor-pointer bg-white"
                   :class="selectedStreamGroup.includes('Group D') ? 'border-[#8C2826] ring-2 ring-[#8C2826]/15 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
              <input type="radio" name="stream_group" x-model="selectedStreamGroup" value="Group D: English, History, Pol Sci / Legal, Psych, Entr / Economics" class="mt-1 text-[#8C2826] focus:ring-[#8C2826]">
              <div class="space-y-1">
                <div class="flex items-center gap-1.5">
                  <span class="font-extrabold text-slate-900 text-xs">Humanities Stream (Group D)</span>
                  <span class="text-[9px] font-bold bg-purple-50 text-purple-800 border border-purple-200 px-2 py-0.5 rounded tracking-wide">LAW / CIVIL SERVICES</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">English, History, Political Science / Legal, Psychology, Entr / Economics</p>
              </div>
            </label>
          </div>

          {{-- Academic Group Selection for Books & Notes --}}
          <div class="p-4 rounded-xl bg-white border border-slate-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-slate-800 uppercase tracking-wide">Books &amp; Notes Checklist Group</span>
                <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] border border-[#FECACA] px-2 py-0.5 rounded">Auto-synced from stream</span>
              </div>
              <p class="text-slate-500 text-[11px] mt-0.5">Determines the exact textbooks and notebook checklists prescribed for Grade XI &amp; XII</p>
            </div>
            <div class="flex items-center gap-2">
              <select name="book_note_group_id" x-model="selectedBookNoteGroupId" @change="fetchBooksNotesChecklist()"
                      class="px-3.5 py-2 rounded-xl border border-slate-300 focus:border-[#8C2826] focus:ring-[#8C2826] text-xs font-bold text-slate-800 bg-slate-50">
                <option value="">-- Select Academic Group --</option>
                <template x-for="grp in academicGroupsList" :key="grp.id">
                  <option :value="grp.id" :selected="grp.id == selectedBookNoteGroupId" x-text="grp.name"></option>
                </template>
              </select>
            </div>
          </div>

          {{-- Class X Qualifying Board Information --}}
          <div class="pt-4 border-t border-slate-200 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Class X Qualifying Board Examination Details</span>
              <span class="text-[10px] text-slate-400 font-medium">As per CBSE Grade XI Admission Norms</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Class X Board</label>
                <select name="previous_school_board" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                  <option value="CBSE" selected>CBSE (New Delhi)</option>
                  <option value="ICSE">ICSE / ISC</option>
                  <option value="Tamil Nadu State Board">Tamil Nadu State Board</option>
                  <option value="Other State Board">Other State Board</option>
                  <option value="International / IGCSE">International / IGCSE</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Roll / Reg No.</label>
                <input type="text" name="previous_class" placeholder="e.g. 193158524" value="{{ old('previous_class') }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Percentage / CGPA</label>
                <div class="relative">
                  <input type="number" step="0.01" min="0" max="100" id="previous_percentage" name="previous_percentage" placeholder="e.g. 91.5" value="{{ old('previous_percentage') }}"
                         class="w-full pl-3 pr-8 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-bold text-slate-900 bg-white">
                  <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">%</span>
                </div>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Year of Passing</label>
                <select name="year_of_passing" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                  <option value="2026" selected>2026</option>
                  <option value="2025">2025</option>
                  <option value="2024">2024</option>
                  <option value="2023">2023</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Previous School Attended (Class X)</label>
                <input type="text" name="previous_school_name" placeholder="School name &amp; city" value="{{ old('previous_school_name') }}"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">TC &amp; Promotion Enclosure Verification</label>
                <div class="flex items-center gap-5 pt-2 flex-wrap">
                  <label class="flex items-center gap-2 text-slate-700 font-bold cursor-pointer">
                    <input type="checkbox" name="is_tc_enclosed" value="1" checked class="rounded text-[#8C2826] focus:ring-[#8C2826]">
                    <span>Original TC Enclosed</span>
                  </label>
                  <label class="flex items-center gap-2 text-slate-700 font-bold cursor-pointer">
                    <input type="checkbox" name="is_qualified_promotion" value="Yes" checked class="rounded text-[#8C2826] focus:ring-[#8C2826]">
                    <span>Qualified for Promotion</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- ── Academic Stage Subsection 2: Secondary Stage (Class IX & X) ── --}}
        <div x-show="isSecondary" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="p-6 rounded-2xl border border-slate-200/90 bg-slate-50/50 space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wide">Secondary Stage Academic Details (Class IX &amp; X)</span>
                <span class="text-[10px] font-bold text-slate-700 bg-white px-2.5 py-0.5 rounded-full border border-slate-200 shadow-2xs">CBSE AISSE Stage</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">All India Secondary School Examination (AISSE) curriculum &amp; subject alignment</p>
            </div>
          </div>

          {{-- Class X Material Fee Notice if Grade X --}}
          <div x-show="selectedFee?.material_fee > 0" class="p-4 rounded-xl bg-amber-50/80 border border-amber-200 text-xs text-amber-900 flex items-start gap-3">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
              <span class="font-extrabold block">Class X Board Coaching &amp; Material Package Fee: Rs. 19,500/-</span>
              <p class="text-[11px] text-amber-800 mt-0.5">Includes board exam question bank sets, laboratory practical journals, special revision sessions, and pre-board examinations. Due on or before 10.01.2026.</p>
            </div>
          </div>

          {{-- Clean, Spacious 2-Column Grid (NO cramped checkboxes inside inputs!) --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Mathematics Track</label>
              <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="standard" selected>Mathematics Standard (Code 041 - Science/Commerce)</option>
                <option value="basic">Mathematics Basic (Code 241 - Commerce/Humanities)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Previous School Attended</label>
              <input type="text" name="previous_school_name" placeholder="Previous school &amp; city" value="{{ old('previous_school_name') }}"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Class Last Studied</label>
              <input type="text" name="previous_class" placeholder="e.g. Class 8 / Class 9" value="{{ old('previous_class') }}"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Transfer Certificate (TC) Status</label>
              <label class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:border-[#8C2826] text-slate-800 font-semibold cursor-pointer shadow-2xs transition">
                <input type="checkbox" name="is_tc_enclosed" value="1" checked class="w-4 h-4 rounded text-[#8C2826] focus:ring-[#8C2826]">
                <span>Original Transfer Certificate (TC) Enclosed with Application</span>
              </label>
            </div>
          </div>
        </div>

        {{-- ── Academic Stage Subsection 3: Middle Stage (Class VI – VIII) ── --}}
        <div x-show="isMiddle" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="p-6 rounded-2xl border border-slate-200/90 bg-slate-50/50 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wide">Middle Stage Academic Details (Class VI – VIII)</span>
                <span class="text-[10px] font-bold text-slate-700 bg-white px-2.5 py-0.5 rounded-full border border-slate-200 shadow-2xs">NEP Middle Stage</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">Experiential learning, 3-language formula, and practical science foundations</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Third Language (L3)</label>
              <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="Hindi" selected>Hindi (Foundation / Introductory)</option>
                <option value="Sanskrit">Sanskrit (Classical Indian Language)</option>
                <option value="Tamil">Tamil (Basic Language)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Previous School Attended</label>
              <input type="text" name="previous_school_name" placeholder="School name &amp; city" value="{{ old('previous_school_name') }}"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Class Last Studied</label>
              <input type="text" name="previous_class" placeholder="e.g. Class 5 / Class 6" value="{{ old('previous_class') }}"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Transfer Certificate (TC) Status</label>
              <label class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:border-[#8C2826] text-slate-800 font-semibold cursor-pointer shadow-2xs transition">
                <input type="checkbox" name="is_tc_enclosed" value="1" checked class="w-4 h-4 rounded text-[#8C2826] focus:ring-[#8C2826]">
                <span>Original Transfer Certificate (TC) Enclosed</span>
              </label>
            </div>
          </div>
        </div>

        {{-- ── Academic Stage Subsection 4: Primary Stage (Class I – V) ── --}}
        <div x-show="isPrimary" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="p-6 rounded-2xl border border-slate-200/90 bg-slate-50/50 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wide">Primary Stage Academic Details (Class I – V)</span>
                <span class="text-[10px] font-bold text-slate-700 bg-white px-2.5 py-0.5 rounded-full border border-slate-200 shadow-2xs">Preparatory Stage</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">Foundational literacy, numeracy, discovery-based learning, and creative arts</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Previous School / Kindergarten Attended</label>
              <input type="text" name="previous_school_name" placeholder="Nursery / Kindergarten / School name" value="{{ old('previous_school_name') }}"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Learning &amp; Activity Interests</label>
              <input type="text" placeholder="e.g. Drawing, Music, Sports, Mental Math"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>
          </div>
        </div>

        {{-- ── Academic Stage Subsection 5: Kindergarten (Pre-KG, LKG, UKG) ── --}}
        <div x-show="isKindergarten" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="p-6 rounded-2xl border border-slate-200/90 bg-slate-50/50 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-900 uppercase tracking-wide">Kindergarten &amp; Early Childhood Details (Pre-KG, LKG, UKG)</span>
                <span class="text-[10px] font-bold text-slate-700 bg-white px-2.5 py-0.5 rounded-full border border-slate-200 shadow-2xs">Foundational ECCE</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">Child habits, comfort routine, and authorized guardian escort details</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Toilet Training Status</label>
              <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="trained" selected>Fully Toilet Trained</option>
                <option value="training">In Training / Needs Occasional Help</option>
                <option value="diapers">Requires Diaper Assistance</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Food / Eating Habits</label>
              <input type="text" placeholder="e.g. Vegetarian, Eats independently"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Authorized Escort for Child Pickup</label>
              <input type="text" placeholder="Name &amp; phone of authorized person"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-900 bg-white">
            </div>
          </div>
        </div>
      </div>


      {{-- Section 1.4: Official Government Registry & Uniform Sizing --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Official Identification &amp; Uniform Sizing Records</h3>
              <p class="text-[11px] text-slate-500 font-medium">Aadhaar, EMIS PEN number, student email, and kit measurements</p>
            </div>
          </div>
          <div class="hidden sm:block h-px bg-slate-100 flex-1 ml-4"></div>
        </div>

        {{-- Row 1: Aadhaar, EMIS, Student Email (Balanced 3-Column Grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          {{-- Aadhaar Number --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="aadhaar_no" class="block text-xs font-bold text-slate-700">Student Aadhaar Number</label>
              <span id="aadhaar_no_badge" class="text-[10px] font-semibold text-slate-400">12 digits (Optional)</span>
            </div>
            <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no') }}"
                   inputmode="numeric" maxlength="12" pattern="[0-9]{12}"
                   oninput="this.value=this.value.replace(/\D/g,'').slice(0,12); updateAadhaarBadge(this, 'aadhaar_no_badge')"
                   onblur="if(this.value.length===12){ checkAadhaarUniqueness(this.value); }"
                   placeholder="12-digit Aadhaar"
                   class="w-full px-4 py-3 rounded-xl border @error('aadhaar_no') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition tracking-wider bg-white">
            @error('aadhaar_no') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="aadhaar_no_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          {{-- EMIS / PEN Number (Explicitly Optional) --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="emis_no" class="block text-xs font-bold text-slate-700">EMIS / PEN Number <span class="font-normal text-slate-400 text-[11px]">(Optional)</span></label>
              <span id="emis_no_badge" class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">Optional</span>
            </div>
            <input type="text" id="emis_no" name="emis_no" value="{{ old('emis_no') }}" maxlength="30"
                   oninput="checkEmisUniqueDebounced(this)"
                   onblur="checkEmisUnique(this)"
                   placeholder="e.g. 33020100101234 (Leave blank if not allocated)"
                   class="w-full px-4 py-3 rounded-xl border @error('emis_no') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            <p class="text-[10.5px] text-slate-400 mt-1">State EMIS / Permanent Education Number (can be updated later)</p>
            @error('emis_no') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="emis_no_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          {{-- Student Email Address --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="email" class="block text-xs font-bold text-slate-700">Student Email Address</label>
              <span id="email_badge" class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">Optional</span>
            </div>
            <input type="email" id="email" name="email" maxlength="100" value="{{ old('email') }}"
                   oninput="validateEmailLive(this, 'email_client_err', false); checkEmailUniqueDebounced(this)"
                   onblur="validateEmailLive(this, 'email_client_err', false); checkEmailUnique(this)"
                   placeholder="student@example.com (Class 9+)"
                   class="w-full px-4 py-3 rounded-xl border @error('email') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            @error('email') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="email_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>
        </div>

        {{-- Row 2: Physical Identification Marks (Balanced 2-Column Grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Physical Identification Mark 1</label>
            <input type="text" name="identification_mark_1" value="{{ old('identification_mark_1') }}" placeholder="e.g. A mole on the right cheek"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Physical Identification Mark 2</label>
            <input type="text" name="identification_mark_2" value="{{ old('identification_mark_2') }}" placeholder="e.g. A scar on the left forehead"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>
        </div>

        {{-- Row 3: Pincode, Dress Size, Shoe Size (Balanced 3-Column Grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          {{-- Pincode --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="pincode" class="block text-xs font-bold text-slate-700">Residential Pincode</label>
              <span id="pincode_badge" class="text-[10px] font-semibold text-slate-400">6 digits (Optional)</span>
            </div>
            <input type="text" id="pincode" name="pincode" value="{{ old('pincode') }}"
                   inputmode="numeric" maxlength="6" pattern="[1-9][0-9]{5}"
                   oninput="this.value=this.value.replace(/\D/g,'').slice(0,6); updatePincodeBadge(this, 'pincode_badge')"
                   placeholder="6-digit PIN"
                   class="w-full px-4 py-3 rounded-xl border @error('pincode') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition tracking-wider bg-white">
            @error('pincode') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="pincode_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          {{-- Dress / Uniform Size --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-700">Uniform Dress Size</label>
              <span x-show="selectedDressSize === 'Other'" class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-1.5 py-0.5 rounded">Custom Size</span>
            </div>
            <select x-model="selectedDressSize"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">-- Select Dress Size --</option>
              <option value="Size 20 (Pre-KG)">Size 20 (Pre-KG / Age 2–3)</option>
              <option value="Size 22 (LKG)">Size 22 (LKG / Age 3–4)</option>
              <option value="Size 24 (UKG)">Size 24 (UKG / Age 4–5)</option>
              <option value="Size 26 (Class 1-2)">Size 26 (Class 1–2 / Age 5–7)</option>
              <option value="Size 28 (Class 3-4)">Size 28 (Class 3–4 / Age 7–9)</option>
              <option value="Size 30 (Class 5-6)">Size 30 (Class 5–6 / Age 9–11)</option>
              <option value="Size 32 (Class 7-8)">Size 32 (Class 7–8 / Age 11–13)</option>
              <option value="Size 34 (Class 9-10)">Size 34 (Class 9–10 / Age 13–15)</option>
              <option value="Size 36 (Class 11 / Small)">Size 36 (Class 11 / Small)</option>
              <option value="Size 38 (Class 12 / Medium)">Size 38 (Class 12 / Medium)</option>
              <option value="Size 40 (Large)">Size 40 (Large)</option>
              <option value="Size 42 (Extra Large)">Size 42 (Extra Large)</option>
              <option value="Other">Other (Custom Measurement)</option>
            </select>
            <div x-show="selectedDressSize === 'Other'" x-transition class="mt-2">
              <input type="text" x-model="customDressSize" placeholder="Custom size (e.g. Chest 30, Length 32)"
                     class="w-full px-4 py-2.5 rounded-xl border border-[#FECACA] focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium bg-white">
            </div>
            <input type="hidden" name="dress_size" :value="effectiveDressSize">
          </div>

          {{-- Shoe Size --}}
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-700">Shoe Size (UK / India)</label>
              <span x-show="selectedShoeSize === 'Other'" class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-1.5 py-0.5 rounded">Custom Size</span>
            </div>
            <select x-model="selectedShoeSize"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
              <option value="">-- Select Shoe Size --</option>
              <optgroup label="Kids Sizing (Pre-KG to Class 3)">
                <option value="Size 8 (Kids)">Size 8 (Kids)</option>
                <option value="Size 9 (Kids)">Size 9 (Kids)</option>
                <option value="Size 10 (Kids)">Size 10 (Kids)</option>
                <option value="Size 11 (Kids)">Size 11 (Kids)</option>
                <option value="Size 12 (Kids)">Size 12 (Kids)</option>
                <option value="Size 13 (Kids)">Size 13 (Kids)</option>
              </optgroup>
              <optgroup label="Junior Sizing (Class 4 to 6)">
                <option value="Size 1 (Junior)">Size 1 (Junior)</option>
                <option value="Size 2 (Junior)">Size 2 (Junior)</option>
                <option value="Size 3 (Junior)">Size 3 (Junior)</option>
              </optgroup>
              <optgroup label="Adult Sizing (Class 7 to 12)">
                <option value="Size 4 (Adult)">Size 4 (Adult)</option>
                <option value="Size 5 (Adult)">Size 5 (Adult)</option>
                <option value="Size 6 (Adult)">Size 6 (Adult)</option>
                <option value="Size 7 (Adult)">Size 7 (Adult)</option>
                <option value="Size 8 (Adult)">Size 8 (Adult)</option>
                <option value="Size 9 (Adult)">Size 9 (Adult)</option>
                <option value="Size 10 (Adult)">Size 10 (Adult)</option>
              </optgroup>
              <option value="Other">Other (Custom Size)</option>
            </select>
            <div x-show="selectedShoeSize === 'Other'" x-transition class="mt-2">
              <input type="text" x-model="customShoeSize" placeholder="Custom shoe size (e.g. Size 10.5 Wide)"
                     class="w-full px-4 py-2.5 rounded-xl border border-[#FECACA] focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium bg-white">
            </div>
            <input type="hidden" name="shoe_size" :value="effectiveShoeSize">
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── STEP 2: PARENT, FAMILY & CAMPUS FACILITIES ─────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="section-parents" class="bg-white p-7 sm:p-10 lg:p-12 rounded-3xl border border-slate-200/90 shadow-sm space-y-9 print-card scroll-mt-24">
      {{-- Step Header --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
          <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-2xl bg-[#8C2826] text-white flex items-center justify-center font-extrabold text-sm shadow-sm">2</span>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Parent, Family &amp; Campus Facilities</h2>
          </div>
          <p class="text-xs text-slate-500 font-medium mt-1.5 pl-12">Primary family contacts, authorized campus security visitor passes, bus transit, and enrichment programs.</p>
        </div>
        <span class="step-badge self-start sm:self-auto text-xs font-bold text-[#8C2826] bg-[#FFF5F5] px-4 py-1.5 rounded-full border border-[#FECACA] shadow-2xs">Step 2 of 3</span>
      </div>

      {{-- Section 2.1: Father / Primary Parent Details --}}
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Father / Primary Parent Profile</h3>
          <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full shadow-2xs">Authorized Visitor Pass Escort #1</span>
        </div>

        {{-- Father Photo Upload for Visitor Pass (Hidden by default, viewable via View button) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-5 rounded-2xl bg-slate-50/70 border border-slate-200">
          <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
            <template x-if="fatherPhotoPreview">
              <div class="w-full h-full bg-emerald-50/70 flex flex-col items-center justify-center text-center p-1">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-2xs">✓</span>
                <span class="text-[8px] font-extrabold text-emerald-800 uppercase mt-0.5">Attached</span>
              </div>
            </template>
            <template x-if="!fatherPhotoPreview">
              <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-bold text-slate-800">Father Photograph (For Campus Visitor Pass)</label>
            <p class="text-[11px] text-slate-500 mt-0.5">Used for physical &amp; digital Parent Visitor Pass for campus security access. Uploaded photo remains hidden by default.</p>
            <div class="mt-2.5">
              <div x-show="!fatherPhotoPreview">
                <input type="file" id="father_photo_input" name="father_photo" accept="image/*" @change="handleFatherPhotoChange($event)"
                       class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="fatherPhotoPreview" class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium shadow-2xs">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="truncate max-w-[180px]" x-text="fatherPhotoFileName"></span>
                  <span class="text-[10px] text-slate-400" x-text="fatherPhotoFileSize"></span>
                </div>
                <button type="button" @click="viewFatherPhoto()"
                        class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span>View</span>
                </button>
                <button type="button" @click="clearFatherPhoto()" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        {{-- Row 1: Name & Mobile (2 Columns) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="parent_name" class="block text-xs font-bold text-slate-700 mb-2">Father / Primary Parent Name <span class="text-rose-500">*</span></label>
            <input type="text" id="parent_name" name="parent_name" required maxlength="100" value="{{ old('parent_name') }}"
                   placeholder="Father full name"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            @error('parent_name') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="parent_name_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="parent_mobile" class="block text-xs font-bold text-slate-700">Father Mobile Number <span class="text-rose-500">*</span></label>
              <span id="parent_mobile_badge" class="text-[10px] font-semibold text-slate-400">10 digits required</span>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-slate-400 select-none">+91</span>
              <input type="tel" id="parent_mobile" name="parent_mobile" required
                     inputmode="numeric" maxlength="10" minlength="10" pattern="[6-9][0-9]{9}"
                     value="{{ old('parent_mobile') }}"
                     oninput="formatPhoneInput(this); updatePhoneBadge(this, 'parent_mobile_badge', true)"
                     onblur="validatePhoneLive(this, 'parent_mobile_client_err', 'parent_mobile_badge', true)"
                     placeholder="9876543210"
                     class="w-full pl-12 pr-4 py-3 rounded-xl border @error('parent_mobile') border-rose-400 ring-2 ring-rose-100 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-semibold transition text-slate-900 tracking-wider bg-white">
            </div>
            @error('parent_mobile') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="parent_mobile_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>
        </div>

        {{-- Row 2: Email, Occupation, Qualification (3 Columns) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div>
            <label for="parent_email" class="block text-xs font-bold text-slate-700 mb-2">Father Email Address</label>
            <input type="email" id="parent_email" name="parent_email" maxlength="100" value="{{ old('parent_email') }}"
                   oninput="validateEmailLive(this, 'parent_email_client_err', false)"
                   onblur="validateEmailLive(this, 'parent_email_client_err', false)"
                   placeholder="father@example.com"
                   class="w-full px-4 py-3 rounded-xl border @error('parent_email') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            @error('parent_email') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="parent_email_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <label for="father_occupation" class="block text-xs font-bold text-slate-700 mb-2">Father Occupation</label>
            <input type="text" id="father_occupation" name="father_occupation" maxlength="100" value="{{ old('father_occupation') }}"
                   placeholder="e.g. Engineer / Business"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <label for="father_qualification" class="block text-xs font-bold text-slate-700 mb-2">Father Qualification</label>
            <input type="text" id="father_qualification" name="father_qualification" maxlength="100" value="{{ old('father_qualification') }}"
                   placeholder="e.g. B.Tech / MBA / Post Graduate"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>
        </div>

        {{-- Row 3: Income & Aadhaar (2 Columns) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="father_income" class="block text-xs font-bold text-slate-700 mb-2">Father Annual Income (₹)</label>
            <input type="text" id="father_income" name="father_income" maxlength="100" value="{{ old('father_income') }}"
                   placeholder="e.g. 6,00,000 / year"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="father_aadhaar" class="block text-xs font-bold text-slate-700">Father Aadhaar Number</label>
              <span id="father_aadhaar_badge" class="text-[10px] font-semibold text-slate-400">12 digits (Optional)</span>
            </div>
            <input type="text" id="father_aadhaar" name="father_aadhaar" value="{{ old('father_aadhaar') }}"
                   inputmode="numeric" maxlength="12" pattern="[0-9]{12}"
                   oninput="this.value=this.value.replace(/\D/g,'').slice(0,12); updateAadhaarBadge(this, 'father_aadhaar_badge')"
                   placeholder="12-digit Aadhaar"
                   class="w-full px-4 py-3 rounded-xl border @error('father_aadhaar') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 tracking-wider bg-white">
            @error('father_aadhaar') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="father_aadhaar_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>
        </div>
      </div>

      {{-- Section 2.2: Mother Details --}}
      <div class="space-y-6 pt-8 border-t border-slate-100">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mother Profile</h3>
          <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full shadow-2xs">Authorized Visitor Pass Escort #2</span>
        </div>

        {{-- Mother Photo Upload for Visitor Pass (Hidden by default, viewable via View button) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-5 rounded-2xl bg-slate-50/70 border border-slate-200">
          <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
            <template x-if="motherPhotoPreview">
              <div class="w-full h-full bg-emerald-50/70 flex flex-col items-center justify-center text-center p-1">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-2xs">✓</span>
                <span class="text-[8px] font-extrabold text-emerald-800 uppercase mt-0.5">Attached</span>
              </div>
            </template>
            <template x-if="!motherPhotoPreview">
              <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-bold text-slate-800">Mother Photograph (For Campus Visitor Pass)</label>
            <p class="text-[11px] text-slate-500 mt-0.5">Used for physical &amp; digital Parent Visitor Pass for campus security access. Uploaded photo remains hidden by default.</p>
            <div class="mt-2.5">
              <div x-show="!motherPhotoPreview">
                <input type="file" id="mother_photo_input" name="mother_photo" accept="image/*" @change="handleMotherPhotoChange($event)"
                       class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="motherPhotoPreview" class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium shadow-2xs">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="truncate max-w-[180px]" x-text="motherPhotoFileName"></span>
                  <span class="text-[10px] text-slate-400" x-text="motherPhotoFileSize"></span>
                </div>
                <button type="button" @click="viewMotherPhoto()"
                        class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span>View</span>
                </button>
                <button type="button" @click="clearMotherPhoto()" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="mother_name" class="block text-xs font-bold text-slate-700 mb-2">Mother Full Name</label>
            <input type="text" id="mother_name" name="mother_name" maxlength="100" value="{{ old('mother_name') }}"
                   placeholder="Mother full name"
                   class="w-full px-4 py-3 rounded-xl border @error('mother_name') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            @error('mother_name') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="mother_name_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="mother_mobile" class="block text-xs font-bold text-slate-700">Mother Mobile Number</label>
              <span id="mother_mobile_badge" class="text-[10px] font-semibold text-slate-400">10 digits (Optional)</span>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-slate-400 select-none">+91</span>
              <input type="tel" id="mother_mobile" name="mother_mobile"
                     inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}"
                     value="{{ old('mother_mobile') }}"
                     oninput="formatPhoneInput(this); updatePhoneBadge(this, 'mother_mobile_badge', false)"
                     onblur="validatePhoneLive(this, 'mother_mobile_client_err', 'mother_mobile_badge', false)"
                     placeholder="10-digit mobile"
                     class="w-full pl-12 pr-4 py-3 rounded-xl border @error('mother_mobile') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-semibold transition text-slate-900 tracking-wider bg-white">
            </div>
            @error('mother_mobile') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="mother_mobile_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div>
            <label for="mother_email" class="block text-xs font-bold text-slate-700 mb-2">Mother Email Address</label>
            <input type="email" id="mother_email" name="mother_email" maxlength="100" value="{{ old('mother_email') }}"
                   oninput="validateEmailLive(this, 'mother_email_client_err', false)"
                   onblur="validateEmailLive(this, 'mother_email_client_err', false)"
                   placeholder="mother@example.com"
                   class="w-full px-4 py-3 rounded-xl border @error('mother_email') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
            @error('mother_email') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="mother_email_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <label for="mother_occupation" class="block text-xs font-bold text-slate-700 mb-2">Mother Occupation</label>
            <input type="text" id="mother_occupation" name="mother_occupation" maxlength="100" value="{{ old('mother_occupation') }}"
                   placeholder="e.g. Teacher / Homemaker / Engineer"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <label for="mother_qualification" class="block text-xs font-bold text-slate-700 mb-2">Mother Qualification</label>
            <input type="text" id="mother_qualification" name="mother_qualification" maxlength="100" value="{{ old('mother_qualification') }}"
                   placeholder="e.g. M.Sc / B.Ed / Graduate"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>
        </div>
      </div>

      {{-- Section 2.3: Guardian Details (Optional) --}}
      <div class="space-y-6 pt-8 border-t border-slate-100">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Local Guardian Profile <span class="text-slate-400 font-medium normal-case tracking-normal">(If Applicable)</span></h3>

        {{-- Guardian Photo Upload for Visitor Pass (Hidden by default, viewable via View button) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-5 rounded-2xl bg-slate-50/70 border border-slate-200">
          <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
            <template x-if="guardianPhotoPreview">
              <div class="w-full h-full bg-emerald-50/70 flex flex-col items-center justify-center text-center p-1">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center shadow-2xs">✓</span>
                <span class="text-[8px] font-extrabold text-emerald-800 uppercase mt-0.5">Attached</span>
              </div>
            </template>
            <template x-if="!guardianPhotoPreview">
              <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-bold text-slate-800">Guardian Photograph (For Campus Visitor Pass)</label>
            <p class="text-[11px] text-slate-500 mt-0.5">Will appear on the Parent &amp; Guardian Visitor Pass. Uploaded photo remains hidden by default.</p>
            <div class="mt-2.5">
              <div x-show="!guardianPhotoPreview">
                <input type="file" id="guardian_photo_input" name="guardian_photo" accept="image/*" @change="handleGuardianPhotoChange($event)"
                       class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="guardianPhotoPreview" class="flex items-center gap-2 flex-wrap">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium shadow-2xs">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="truncate max-w-[180px]" x-text="guardianPhotoFileName"></span>
                  <span class="text-[10px] text-slate-400" x-text="guardianPhotoFileSize"></span>
                </div>
                <button type="button" @click="viewGuardianPhoto()"
                        class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span>View</span>
                </button>
                <button type="button" @click="clearGuardianPhoto()" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div>
            <label for="guardian_name" class="block text-xs font-bold text-slate-700 mb-2">Guardian Name</label>
            <input type="text" id="guardian_name" name="guardian_name" maxlength="100" value="{{ old('guardian_name') }}"
                   placeholder="e.g. Grandparent / Local Guardian"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="guardian_mobile" class="block text-xs font-bold text-slate-700">Guardian Mobile Number</label>
              <span id="guardian_mobile_badge" class="text-[10px] font-semibold text-slate-400">10 digits (Optional)</span>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-slate-400 select-none">+91</span>
              <input type="tel" id="guardian_mobile" name="guardian_mobile"
                     inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}"
                     value="{{ old('guardian_mobile') }}"
                     oninput="formatPhoneInput(this); updatePhoneBadge(this, 'guardian_mobile_badge', false)"
                     onblur="validatePhoneLive(this, 'guardian_mobile_client_err', 'guardian_mobile_badge', false)"
                     placeholder="10-digit mobile"
                     class="w-full pl-12 pr-4 py-3 rounded-xl border @error('guardian_mobile') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-semibold transition text-slate-900 tracking-wider bg-white">
            </div>
            @error('guardian_mobile') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
            <p id="guardian_mobile_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>
          </div>

          <div>
            <label for="guardian_relation" class="block text-xs font-bold text-slate-700 mb-2">Relationship to Student</label>
            <input type="text" id="guardian_relation" name="guardian_relation" maxlength="50" value="{{ old('guardian_relation') }}"
                   placeholder="e.g. Uncle / Aunt / Grandfather"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>
        </div>
      </div>

      {{-- Section 2.4: Sibling Enrollment in Same School --}}
      <div class="space-y-4 pt-8 border-t border-slate-100">
        <div class="flex items-center gap-3">
          <input type="checkbox" id="has_sibling" x-model="hasSibling" class="w-4 h-4 text-[#8C2826] rounded border-slate-300 focus:ring-[#8C2826] cursor-pointer">
          <label for="has_sibling" class="text-xs font-bold text-slate-800 cursor-pointer select-none flex items-center gap-2">
            <span>Does the student have a sibling currently studying in this school?</span>
            <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-2 py-0.5 rounded border border-[#FECACA]">Sibling Concession Eligible</span>
          </label>
        </div>

        <div x-show="hasSibling" x-transition class="p-6 rounded-2xl bg-slate-50/70 border border-slate-200 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            {{-- Field 1: Sibling Roll No. (Editable input field with live lookup) --}}
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-slate-700">Sibling Roll No. <span class="text-rose-500">*</span></label>
                <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-1.5 py-0.5 rounded border border-[#FECACA]">Auto-Fetch</span>
              </div>
              <div class="relative">
                <input type="text" id="sibling_roll_no" name="sibling_roll_no" x-model="siblingRollNo"
                       @input.debounce.400ms="fetchSiblingDetails()"
                       @change="fetchSiblingDetails()"
                       placeholder="Enter Roll No. (e.g. PKGA004)"
                       class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-bold text-slate-900 bg-white transition shadow-2xs tracking-wider">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <template x-if="siblingLookupStatus === 'loading'">
                    <svg class="animate-spin h-4 w-4 text-[#8C2826]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                  </template>
                  <template x-if="siblingLookupStatus === 'found'">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  </template>
                  <template x-if="siblingLookupStatus === 'not_found' || siblingLookupStatus === 'error'">
                    <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </template>
                </div>
              </div>
              <input type="hidden" name="sibling_admission_no" :value="siblingAdmissionNo || siblingRollNo">
            </div>

            {{-- Field 2: Sibling Student Name (Auto-filled) --}}
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-2">Sibling Student Name</label>
              <input type="text" name="sibling_name" x-model="siblingName" readonly
                     placeholder="Auto-filled from Roll No."
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 cursor-default select-none shadow-2xs">
            </div>

            {{-- Field 3: Sibling Class & Section (Auto-filled) --}}
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-2">Sibling Class &amp; Section</label>
              <input type="text" name="sibling_class" x-model="siblingClass" readonly
                     placeholder="Auto-filled from Roll No."
                     class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 cursor-default select-none shadow-2xs">
            </div>
          </div>

          {{-- Lookup Status Feedback Message --}}
          <div x-show="siblingLookupMessage" x-transition class="text-[11px] font-semibold flex items-center gap-1.5 pt-1"
               :class="{
                 'text-emerald-700': siblingLookupStatus === 'found',
                 'text-rose-600': siblingLookupStatus === 'not_found' || siblingLookupStatus === 'error',
                 'text-[#8C2826]': siblingLookupStatus === 'loading'
               }">
            <svg x-show="siblingLookupStatus === 'found'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <svg x-show="siblingLookupStatus === 'not_found' || siblingLookupStatus === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="siblingLookupMessage"></span>
          </div>
        </div>
      </div>

      {{-- Section 2.5: Residential Address & Household Income --}}
      <div class="space-y-4 pt-8 border-t border-slate-100">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Residential Address &amp; Family Financial Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-2">Residential Street Address</label>
            <input type="text" name="address" value="{{ old('address') }}"
                   placeholder="Complete door no, street name, and locality"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-900 bg-white">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Annual Family Income (₹)</label>
            <input type="number" step="0.01" name="annual_family_income" value="{{ old('annual_family_income') }}"
                   placeholder="e.g. 500000"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition tabular-nums text-slate-900 bg-white">
          </div>
        </div>
      </div>

      {{-- Section 2.6: Campus Facilities & Student Programs --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <div>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Campus Facilities &amp; Co-Curricular Programs</h3>
          <p class="text-xs text-slate-500 font-medium mt-1">Bus transit, supervised afternoon activities (ASP), and complimentary hobby clubs.</p>
        </div>

        {{-- Facilities Cards Grid --}}
        <div class="grid grid-cols-1 gap-6">
          {{-- 1. School Transport Facility --}}
          <div class="p-6 rounded-2xl border transition-all"
               :class="transportRequired ? 'bg-[#FFF5F5]/60 border-[#FECACA] ring-2 ring-[#FEE2E2] shadow-xs' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-50'">
            <div class="flex items-start gap-4">
              <input type="checkbox" id="transport_check" x-model="transportRequired"
                     class="mt-1 w-4 h-4 text-[#8C2826] rounded border-slate-300 focus:ring-[#8C2826] cursor-pointer">
              <div class="space-y-4 flex-1">
                <label for="transport_check" class="cursor-pointer select-none block">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-extrabold text-[#380E0D]">School Transport Facility</span>
                    <span class="text-[10px] font-bold text-[#8C2826] bg-white px-2 py-0.5 rounded border border-[#FECACA] shadow-2xs">GPS Monitored</span>
                  </div>
                  <span class="text-xs text-slate-500 font-medium block mt-1 leading-relaxed">
                    GPS-monitored school bus/van transit with designated boarding points and distance-based annual fare schedule.
                  </span>
                </label>

                {{-- Hidden Transport Form Inputs for Database --}}
                <input type="hidden" name="transport_route_id" :value="transportRequired ? selectedRouteId : ''">
                <input type="hidden" name="transport_stop_id" :value="transportRequired ? selectedStopId : ''">
                <input type="hidden" name="transport_drop_stop_id" :value="transportRequired ? (selectedDropStopId || selectedStopId) : ''">
                <input type="hidden" name="transport_pickup_time" :value="transportRequired ? transportPickupTime : ''">
                <input type="hidden" name="transport_drop_time" :value="transportRequired ? transportDropTime : ''">
                <input type="hidden" name="transport_distance_km" :value="transportRequired ? transportDistanceKm : 0">
                <input type="hidden" name="transport_fee" :value="transportRequired ? transportFee : 0">

                <div x-show="transportRequired" x-transition class="space-y-4 pt-3 border-t border-[#FECACA]">
                  {{-- Assigned Vehicle Info Banner --}}
                  <template x-if="currentRouteVehicle">
                    <div class="p-3 bg-white rounded-xl border border-amber-200 flex items-center justify-between text-xs shadow-2xs">
                      <div class="flex items-center gap-2.5">
                        <span class="text-base">🚐</span>
                        <div>
                          <span class="font-bold text-slate-800" x-text="currentRouteVehicle.vehicle_number + ' (' + (currentRouteVehicle.vehicle_type || 'Van') + ')'"></span>
                          <span class="text-[11px] text-slate-500 block">Driver: <strong x-text="currentRouteVehicle.driver_name || 'Assigned Driver'"></strong> &bull; Capacity: <span x-text="(currentRouteVehicle.seating_capacity || currentRouteVehicle.capacity || 25) + ' seats'"></span></span>
                        </div>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">Seat Available</span>
                    </div>
                  </template>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1.5">Select Transport Route</label>
                      <select x-model="selectedRouteId" @change="selectedStopId = ''; selectedDropStopId = ''; transportPickupTime = ''; transportDropTime = ''"
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-[#8C2826]">
                        <option value="">-- Choose School Route --</option>
                        <template x-for="route in transportRoutesData" :key="route.id">
                          <option :value="route.id" x-text="route.route_name + ' (' + (route.vehicle ? route.vehicle.vehicle_number : 'Transit') + ')'"></option>
                        </template>
                      </select>
                    </div>

                    <div x-show="selectedRouteId">
                      <label class="block text-xs font-bold text-slate-700 mb-1.5">Pickup Boarding Stop</label>
                      <select x-model="selectedStopId" @change="let st = currentRouteStops.find(s => s.id == selectedStopId); if (st) { transportPickupTime = st.pickup_time || '07:30'; transportDropTime = st.drop_time || '16:30'; if (!selectedDropStopId) selectedDropStopId = st.id; }"
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-[#8C2826]">
                        <option value="">-- Choose Pickup Stop --</option>
                        <template x-for="stop in currentRouteStops" :key="stop.id">
                          <option :value="stop.id" x-text="stop.name + ' • ' + (stop.distance_km ? stop.distance_km + ' km • ' : '') + formatMoney(stop.fare)"></option>
                        </template>
                      </select>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-show="selectedStopId">
                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1.5">Drop Return Stop</label>
                      <select x-model="selectedDropStopId"
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-[#8C2826]">
                        <option value="">Same as Pickup Stop</option>
                        <template x-for="stop in currentRouteStops" :key="stop.id">
                          <option :value="stop.id" x-text="stop.name"></option>
                        </template>
                      </select>
                    </div>

                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1.5">Student Pickup Time</label>
                      <input type="text" x-model="transportPickupTime" placeholder="e.g. 07:25 AM"
                             class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-mono font-semibold text-slate-800 focus:border-[#8C2826]">
                    </div>

                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1.5">Student Drop Time</label>
                      <input type="text" x-model="transportDropTime" placeholder="e.g. 04:35 PM"
                             class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-mono font-semibold text-slate-800 focus:border-[#8C2826]">
                    </div>
                  </div>

                  {{-- Live Stopping, Km & Fee Summary Badge --}}
                  <div x-show="currentStop" class="p-4 bg-white rounded-xl border border-[#FECACA] flex items-center justify-between text-xs shadow-2xs">
                    <div>
                      <span class="font-extrabold text-[#380E0D] block" x-text="currentStop?.name"></span>
                      <span class="text-[11px] text-slate-500" x-text="'Distance: ' + currentStop?.distance_km + ' km' + (currentStop?.landmark ? ' • Landmark: ' + currentStop.landmark : '')"></span>
                    </div>
                    <div class="text-right">
                      <span class="text-[10px] uppercase font-bold text-slate-400 block">Annual Transport Fee</span>
                      <span class="font-extrabold text-[#8C2826] text-sm tabular-nums" x-text="formatMoney(transportFee)"></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- 2. After School Program (ASP) --}}
          <div class="p-6 rounded-2xl border transition-all"
               :class="aspRequired ? 'border-amber-400 bg-amber-50/50 shadow-xs' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-50'">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-start gap-4">
                <input type="checkbox" id="asp_check" name="is_asp" value="1" x-model="aspRequired"
                       class="mt-1 w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500 cursor-pointer">
                <div>
                  <label for="asp_check" class="cursor-pointer select-none">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-extrabold text-slate-900">After School Program (ASP)</span>
                      <span class="text-[10px] font-bold text-amber-800 bg-amber-100/80 px-2 py-0.5 rounded border border-amber-200 shadow-2xs">Enrichment Sports</span>
                    </div>
                    <span class="text-xs text-slate-500 font-medium block mt-1 leading-relaxed">
                      Supervised afternoon activities — Chess, Athletics, Table Tennis, Skating, Volleyball, Throwball, Basketball &amp; Karate.
                    </span>
                  </label>

                  <div class="flex flex-wrap gap-2 mt-3">
                    <span class="inline-flex items-center text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg px-2.5 py-1 shadow-2xs">
                      Term I &mdash; ₹4,000 &nbsp;(Jul &ndash; Oct)
                    </span>
                    <span class="inline-flex items-center text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg px-2.5 py-1 shadow-2xs">
                      Term II &mdash; ₹4,000 &nbsp;(Nov &ndash; Feb)
                    </span>
                  </div>
                </div>
              </div>

              {{-- Timetable toggle button --}}
              <button type="button" @click="aspInfoOpen = !aspInfoOpen"
                      class="shrink-0 text-xs font-semibold text-slate-600 bg-white border border-slate-200 px-3.5 py-1.5 rounded-xl hover:bg-slate-50 shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="aspInfoOpen ? 'Hide Timetable' : 'View Timetable'"></span>
              </button>
            </div>

            {{-- ASP Timetable (collapsible) --}}
            <div x-show="aspInfoOpen" x-transition class="pt-4">
              <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs bg-white">
                <div class="bg-slate-800 px-4 py-2.5 flex items-center gap-2">
                  <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  <span class="text-xs font-semibold text-white tracking-wide uppercase">ASP Weekly Activity Timetable</span>
                </div>
                <table class="w-full text-xs font-medium">
                  <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                      <th class="px-3.5 py-2 text-left text-slate-700 font-bold w-[20%]">Day</th>
                      <th class="px-3 py-2 text-center text-slate-700 font-semibold">Activity 1</th>
                      <th class="px-3 py-2 text-center text-slate-700 font-semibold">Activity 2</th>
                      <th class="px-3 py-2 text-center text-slate-700 font-semibold">Activity 3</th>
                      <th class="px-3 py-2 text-center text-slate-700 font-semibold">Activity 4</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    @php
                      $aspSchedule = [
                        ['day' => 'Monday',    'acts' => ['Chess', 'Athletic', 'Table Tennis', 'Skating']],
                        ['day' => 'Tuesday',   'acts' => ['Volley Ball', 'Throw Ball', 'Basket Ball', 'Karate']],
                        ['day' => 'Wednesday', 'acts' => ['Chess', 'Athletic', 'Table Tennis', 'Skating']],
                        ['day' => 'Thursday',  'acts' => ['Chess', 'Throw Ball', 'Basket Ball', 'Karate']],
                        ['day' => 'Friday',    'acts' => ['Volley Ball', 'Athletic', 'Table Tennis', 'Skating']],
                        ['day' => 'Saturday',  'acts' => ['Volley Ball', 'Throw Ball', 'Basket Ball', 'Karate']],
                      ];
                    @endphp
                    @foreach($aspSchedule as $i => $row)
                      <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                        <td class="px-3.5 py-2 font-bold text-amber-900">{{ $row['day'] }}</td>
                        @foreach($row['acts'] as $act)
                          <td class="px-3 py-2 text-center text-slate-600">{{ $act }}</td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>

            {{-- Term Selector + Fee (only when checked) --}}
            <div x-show="aspRequired" x-transition class="pt-5 mt-4 border-t border-amber-200/80">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-bold text-amber-900 mb-2 uppercase tracking-wide">Select Enrolled Term</label>
                  <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition select-none"
                           :class="aspTerm === 'term1' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-amber-300'">
                      <input type="radio" name="asp_term" value="term1" x-model="aspTerm" class="text-amber-600 focus:ring-amber-500">
                      <div class="flex-1">
                        <span class="text-xs font-extrabold text-amber-900 block">Term I &nbsp;— ₹4,000</span>
                        <span class="text-[11px] text-amber-700">July, August, September, October</span>
                      </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition select-none"
                           :class="aspTerm === 'term2' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-amber-300'">
                      <input type="radio" name="asp_term" value="term2" x-model="aspTerm" class="text-amber-600 focus:ring-amber-500">
                      <div class="flex-1">
                        <span class="text-xs font-extrabold text-amber-900 block">Term II &nbsp;— ₹4,000</span>
                        <span class="text-[11px] text-amber-700">November, December, January, February</span>
                      </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition select-none"
                           :class="aspTerm === 'both' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-amber-300'">
                      <input type="radio" name="asp_term" value="both" x-model="aspTerm" class="text-amber-600 focus:ring-amber-500">
                      <div class="flex-1">
                        <span class="text-xs font-extrabold text-amber-900 block">Both Terms &nbsp;— ₹8,000</span>
                        <span class="text-[11px] text-amber-700">Full Academic Year (Jul – Feb)</span>
                      </div>
                    </label>
                  </div>
                </div>

                {{-- Fee Summary --}}
                <div class="flex flex-col justify-between gap-3">
                  <div>
                    <label class="block text-xs font-bold text-amber-900 mb-2 uppercase tracking-wide">ASP Fee Summary</label>
                    <div class="p-5 rounded-2xl bg-white border border-amber-300 shadow-2xs flex items-center justify-between">
                      <div>
                        <span class="text-[11px] font-bold text-slate-500 block">Term Rate</span>
                        <span class="text-base font-extrabold text-amber-800 tabular-nums">₹4,000 / term</span>
                      </div>
                      <div class="text-right" x-show="aspTerm">
                        <span class="text-[11px] font-bold text-slate-500 block">Total ASP Payable</span>
                        <span class="text-xl font-extrabold text-amber-700 tabular-nums" x-text="formatMoney(aspFeeTotal)"></span>
                      </div>
                    </div>
                  </div>

                  <input type="hidden" name="asp_fee" :value="aspFeeTotal">
                  <input type="hidden" name="asp_term" :value="aspTerm">

                  <div x-show="!aspTerm" class="text-[11px] text-amber-700 font-semibold flex items-center gap-1.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Please select a term to enrol in ASP.</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- 3. Extra Curricular Activities (Complimentary) --}}
          <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/70 pb-3">
              <div>
                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide">Co-Curricular &amp; Hobby Activities</h4>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Select student club &amp; activity preferences. <span class="text-emerald-700 font-bold">Complimentary (No extra charges).</span></p>
              </div>
              <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">Complimentary</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              @foreach($activities as $act)
              <label class="p-3.5 rounded-xl border transition cursor-pointer flex items-center justify-between gap-2.5 select-none"
                     :class="selectedActivities.includes('{{ $act['id'] }}') ? 'bg-[#FFF5F5] border-[#8C2826] shadow-2xs ring-1 ring-[#8C2826]' : 'bg-white border-slate-200 hover:border-slate-300'">
                <div class="flex items-center gap-2 min-w-0">
                  <input type="checkbox" name="activities[]" value="{{ $act['id'] }}" x-model="selectedActivities"
                         class="w-4 h-4 text-[#8C2826] rounded border-slate-300 focus:ring-[#8C2826] cursor-pointer">
                  <span class="text-xs font-bold text-slate-800 truncate">{{ $act['name'] }}</span>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 shrink-0">Included</span>
              </label>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── STEP 3: PRESCRIBED BOOKS, FEES, DOCUMENTS & ENROLLMENT ───────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="section-billing" class="bg-white p-7 sm:p-10 lg:p-12 rounded-3xl border border-slate-200/90 shadow-sm space-y-9 print-card scroll-mt-24">
      {{-- Step Header --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
        <div>
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#8C2826] to-[#A83230] text-white flex items-center justify-center font-black text-base shadow-sm ring-4 ring-[#8C2826]/10">3</span>
            <div>
              <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Prescribed Books, Kits, Fees &amp; Billing</h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Prescribed statutory curriculum books, notebooks, fee concessions, documents, and fee collection terms.</p>
            </div>
          </div>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FFF5F5] border border-[#FECACA] shadow-2xs self-start sm:self-auto">
          <span class="w-2 h-2 rounded-full bg-[#8C2826] animate-pulse"></span>
          <span class="text-xs font-extrabold text-[#8C2826] tracking-wide">Step 3 of 3</span>
        </div>
      </div>

      {{-- Section 3.1: Prescribed Books, Notebooks & Academic Materials --}}
      <div x-show="!selectedClassId" class="p-6 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-xs flex items-center gap-3 shadow-2xs">
        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm shrink-0">📚</div>
        <div>
          <span class="font-bold block text-sm">No Class / Standard Selected Yet</span>
          <span class="text-amber-700 mt-0.5 block">Please select an Academic Standard (Class) in Step 1 to preview its prescribed statutory curriculum textbooks, notebooks and school uniform checklist.</span>
        </div>
      </div>

      <div x-show="selectedClassId" x-transition class="space-y-6">
        
        {{-- Section Subheading --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#FFF5F5] text-[#8C2826] border border-[#FECACA] flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Prescribed Books, Notebooks &amp; Uniforms</h3>
                <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-2.5 py-0.5 rounded-full border border-[#FECACA]" x-text="selectedClassName.toUpperCase().startsWith('CLASS') || selectedClassName.toUpperCase().includes('KG') ? selectedClassName : 'Class ' + selectedClassName"></span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium">Statutory curriculum titles, student exercise notebooks and uniform allocation</p>
            </div>
          </div>
          <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 self-start sm:self-auto">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Auto-Assigned from Master Checklist
          </span>
        </div>

        {{-- Custom Sleek Scrollbar Styles for Curriculum Inventory --}}
        <style>
          .curriculum-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #CBD5E1 #F8FAFC;
          }
          .curriculum-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
          }
          .curriculum-scroll::-webkit-scrollbar-track {
            background: #F8FAFC;
            border-radius: 4px;
          }
          .curriculum-scroll::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
          }
          .curriculum-scroll::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
          }
        </style>

        {{-- Interactive Segmented Tabs, Filter & View Controls Bar --}}
        <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50/90 border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 shadow-2xs"
             x-show="!bookNoteLoading && (bookNoteList.books.length > 0 || bookNoteList.notes.length > 0 || (bookNoteList.uniforms && bookNoteList.uniforms.length > 0))">
          
          {{-- Left: Segmented Tab Switcher --}}
          <div class="flex items-center gap-1.5 flex-wrap">
            {{-- Tab: All Columns --}}
            <button type="button"
                    @click="curriculumTab = 'all'"
                    :class="curriculumTab === 'all' ? 'bg-[#8C2826] text-white shadow-xs font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <span>⊞</span>
              <span>All 3 Columns</span>
              <span :class="curriculumTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'" class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" x-text="bookNoteList.books.length + bookNoteList.notes.length + (bookNoteList.uniforms ? bookNoteList.uniforms.length : 0)"></span>
            </button>

            {{-- Tab: Textbooks --}}
            <button type="button"
                    @click="curriculumTab = 'books'"
                    :class="curriculumTab === 'books' ? 'bg-amber-500 text-white shadow-xs font-bold' : 'bg-white text-slate-700 hover:bg-amber-50/60 border border-slate-200 font-semibold'"
                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <span class="w-2 h-2 rounded-full" :class="curriculumTab === 'books' ? 'bg-white' : 'bg-amber-500'"></span>
              <span>Textbooks</span>
              <span :class="curriculumTab === 'books' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-900'" class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" x-text="bookNoteList.books.length"></span>
            </button>

            {{-- Tab: Notebooks --}}
            <button type="button"
                    @click="curriculumTab = 'notes'"
                    :class="curriculumTab === 'notes' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 hover:bg-indigo-50/60 border border-slate-200 font-semibold'"
                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <span class="w-2 h-2 rounded-full" :class="curriculumTab === 'notes' ? 'bg-white' : 'bg-indigo-500'"></span>
              <span>Notebooks</span>
              <span :class="curriculumTab === 'notes' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-900'" class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" x-text="bookNoteList.notes.length"></span>
            </button>

            {{-- Tab: Uniforms --}}
            <button type="button"
                    x-show="bookNoteList.uniforms && bookNoteList.uniforms.length > 0"
                    @click="curriculumTab = 'uniforms'"
                    :class="curriculumTab === 'uniforms' ? 'bg-teal-600 text-white shadow-xs font-bold' : 'bg-white text-slate-700 hover:bg-teal-50/60 border border-slate-200 font-semibold'"
                    class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <span class="w-2 h-2 rounded-full" :class="curriculumTab === 'uniforms' ? 'bg-white' : 'bg-teal-500'"></span>
              <span>Uniforms</span>
              <span :class="curriculumTab === 'uniforms' ? 'bg-white/20 text-white' : 'bg-teal-100 text-teal-900'" class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" x-text="(bookNoteList.uniforms || []).length"></span>
            </button>
          </div>

          {{-- Right: Compact/Expand Toggle, Total Units --}}
          <div class="flex items-center gap-2 flex-wrap">
            {{-- Compact / Expand Height Toggle --}}
            <button type="button"
                    @click="curriculumExpanded = !curriculumExpanded"
                    class="px-2.5 py-1 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 flex items-center gap-1 transition-all cursor-pointer"
                    :title="curriculumExpanded ? 'Switch to compact scrollable view' : 'Expand full list without scrolling'">
              <span x-text="curriculumExpanded ? '⇕ Compact' : '↕ Expand'"></span>
            </button>

            {{-- Prescribed Units Badge --}}
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 font-extrabold text-xs">
              <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span x-text="bookNoteTotalUnits + ' Units Prescribed'"></span>
            </div>
          </div>
        </div>

        {{-- Hidden Form Inputs for Automatic Submission & Snapshotting --}}
        <template x-for="book in bookNoteList.books" :key="'in_b_' + book.id">
          <input type="hidden" name="issued_book_note_ids[]" :value="book.id">
        </template>
        <template x-for="note in bookNoteList.notes" :key="'in_n_' + note.id">
          <input type="hidden" name="issued_book_note_ids[]" :value="note.id">
        </template>
        <template x-for="uniform in (bookNoteList.uniforms || [])" :key="'in_u_' + uniform.sku">
          <input type="hidden" name="issued_book_note_ids[]" :value="uniform.sku">
        </template>
        <input type="hidden" name="custom_kit_items" value="[]">

        {{-- Loading State --}}
        <div x-show="bookNoteLoading" class="py-12 text-center text-xs text-slate-500 font-bold bg-white rounded-3xl border border-slate-200/80 p-8 shadow-xs space-y-3">
          <div class="w-8 h-8 border-3 border-[#8C2826] border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p>Loading prescribed books and learning materials from master inventory...</p>
        </div>

        {{-- Senior Secondary Group Notice --}}
        <div x-show="!bookNoteLoading && isSeniorSecondary && !selectedBookNoteGroupId"
             class="p-6 rounded-3xl bg-purple-50/80 border border-purple-200/90 text-purple-950 text-xs flex items-center gap-4 shadow-xs">
          <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center font-black text-lg shrink-0">
            ℹ️
          </div>
          <div>
            <span class="font-black text-sm block">Please select an Academic Group in Section 1</span>
            <span class="text-xs text-purple-800 mt-0.5 block">For Class XI and XII, the curriculum is strictly customized by academic stream (e.g., BIO &amp; CS GROUP or ACCOUNTS GROUP). Choosing a group will automatically load its respective textbooks and notebooks.</span>
          </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!bookNoteLoading && (!isSeniorSecondary || selectedBookNoteGroupId) && bookNoteList.books.length === 0 && bookNoteList.notes.length === 0 && (!bookNoteList.uniforms || bookNoteList.uniforms.length === 0)"
             class="py-12 px-6 text-center rounded-3xl border border-dashed border-slate-300 bg-white shadow-xs space-y-2">
          <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">📚</div>
          <p class="text-sm font-black text-slate-800">No Books, Notes &amp; Uniforms configured for this class.</p>
          <p class="text-xs text-slate-500">Administrators can register curriculum requirements under Settings &rarr; Book, Note &amp; Uniform Distribution.</p>
        </div>

        {{-- Itemized Details Container (Tabs & Bounded Scrolling) --}}
        <div x-show="!bookNoteLoading && (bookNoteList.books.length > 0 || bookNoteList.notes.length > 0 || (bookNoteList.uniforms && bookNoteList.uniforms.length > 0))" class="space-y-4">

          {{-- VIEW MODE 1: All 3 Columns Side-by-Side (Bounded Fixed-Height Scrolling) --}}
          <div x-show="curriculumTab === 'all'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            {{-- Column 1: Prescribed Textbooks --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col">
              <div class="p-3 px-3.5 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs font-bold shadow-2xs">📚</div>
                  <div>
                    <h4 class="text-xs font-black text-amber-950 uppercase tracking-wide">Textbooks</h4>
                    <span class="text-[10px] text-amber-800 font-medium">Standard curriculum titles</span>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-white text-amber-900 border border-amber-200 shadow-2xs tabular-nums"
                      x-text="bookNoteList.books.length + ' Titles'"></span>
              </div>

              {{-- Bounded Scrollable List --}}
              <div class="p-2 space-y-1 divide-y divide-slate-100 curriculum-scroll"
                   :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 250px; overflow-y: auto;'">
                <template x-for="(book, index) in bookNoteList.books" :key="book.id">
                  <div class="pt-2 first:pt-0 py-1.5 px-2 flex items-center justify-between gap-2.5 hover:bg-amber-50/40 rounded-xl transition-colors">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-semibold text-slate-900 leading-snug" x-text="book.name"></p>
                      <div class="flex items-center gap-2 mt-0.5" x-show="book.sku">
                        <span class="font-mono text-[10px] text-slate-400 font-medium tracking-tight" x-text="book.sku"></span>
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-black tabular-nums bg-amber-50 text-amber-900 border border-amber-200/80 shrink-0">
                      <span class="text-[10px] font-bold text-amber-600 mr-1">Qty:</span>
                      <span x-text="book.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>

              <div class="px-3 py-1.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500 font-medium">
                <span x-text="bookNoteList.books.length + ' titles prescribed'"></span>
                <span x-show="!curriculumExpanded && bookNoteList.books.length > 5" class="text-amber-700 font-semibold">↕ Scroll for more</span>
              </div>
            </div>

            {{-- Column 2: Notebooks & Workbooks --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col">
              <div class="p-3 px-3.5 bg-indigo-50/70 border-b border-indigo-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-2xs">📝</div>
                  <div>
                    <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wide">Notebooks</h4>
                    <span class="text-[10px] text-indigo-800 font-medium">Ruled, unruled &amp; activity</span>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-white text-indigo-900 border border-indigo-200 shadow-2xs tabular-nums"
                      x-text="bookNoteList.notes.length + ' Items'"></span>
              </div>

              {{-- Bounded Scrollable List --}}
              <div class="p-2 space-y-1 divide-y divide-slate-100 curriculum-scroll"
                   :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 250px; overflow-y: auto;'">
                <template x-for="(note, index) in bookNoteList.notes" :key="note.id">
                  <div class="pt-2 first:pt-0 py-1.5 px-2 flex items-center justify-between gap-2.5 hover:bg-indigo-50/40 rounded-xl transition-colors">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-semibold text-slate-900 leading-snug" x-text="note.name"></p>
                      <div class="flex items-center gap-2 mt-0.5" x-show="note.sku">
                        <span class="font-mono text-[10px] text-slate-400 font-medium tracking-tight" x-text="note.sku"></span>
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-black tabular-nums bg-indigo-50 text-indigo-900 border border-indigo-200/80 shrink-0">
                      <span class="text-[10px] font-bold text-indigo-600 mr-1">Qty:</span>
                      <span x-text="note.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>

              <div class="px-3 py-1.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500 font-medium">
                <span x-text="bookNoteList.notes.length + ' items prescribed'"></span>
                <span x-show="!curriculumExpanded && bookNoteList.notes.length > 5" class="text-indigo-700 font-semibold">↕ Scroll for more</span>
              </div>
            </div>

            {{-- Column 3: Prescribed Uniforms --}}
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden flex flex-col"
                 x-show="bookNoteList.uniforms && bookNoteList.uniforms.length > 0">
              <div class="p-3 px-3.5 bg-teal-50/70 border-b border-teal-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-lg bg-teal-600 text-white flex items-center justify-center text-xs font-bold shadow-2xs">👔</div>
                  <div>
                    <h4 class="text-xs font-black text-teal-950 uppercase tracking-wide"
                        x-text="(selectedGender || '').toLowerCase() === 'female' ? 'Uniform (Girls)' : ((selectedGender || '').toLowerCase() === 'male' ? 'Uniform (Boys)' : 'Uniforms')"></h4>
                    <span class="text-[10px] text-teal-800 font-medium"
                          x-text="selectedGender ? (selectedGender.toUpperCase() + ' Standard Kit') : 'Auto-assigned by Standard'"></span>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-white text-teal-900 border border-teal-200 shadow-2xs tabular-nums"
                      x-text="(bookNoteList.uniforms || []).length + ' Items'"></span>
              </div>

              {{-- Bounded Scrollable List --}}
              <div class="p-2 space-y-1 divide-y divide-slate-100 curriculum-scroll"
                   :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 250px; overflow-y: auto;'">
                <template x-for="(uniform, index) in (bookNoteList.uniforms || [])" :key="uniform.sku">
                  <div class="pt-2 first:pt-0 py-1.5 px-2 flex items-center justify-between gap-2.5 hover:bg-teal-50/40 rounded-xl transition-colors">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-semibold text-slate-900 leading-snug" x-text="uniform.name"></p>
                      <div class="flex items-center gap-2 mt-0.5" x-show="uniform.sku">
                        <span class="font-mono text-[10px] text-slate-400 font-medium tracking-tight" x-text="uniform.sku"></span>
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-black tabular-nums bg-teal-50 text-teal-900 border border-teal-200/80 shrink-0">
                      <span class="text-[10px] font-bold text-teal-600 mr-1">Qty:</span>
                      <span x-text="uniform.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>

              <div class="px-3 py-1.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500 font-medium">
                <span x-text="(bookNoteList.uniforms || []).length + ' items prescribed'"></span>
                <span x-show="!curriculumExpanded && (bookNoteList.uniforms || []).length > 5" class="text-teal-700 font-semibold">↕ Scroll for more</span>
              </div>
            </div>

          </div>

          {{-- VIEW MODE 2: Dedicated Textbooks Tab (Multi-Column Compact Grid) --}}
          <div x-show="curriculumTab === 'books'" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="p-3.5 px-4 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-2xs">📚</div>
                <div>
                  <h4 class="text-xs font-black text-amber-950 uppercase tracking-wide">Prescribed Textbooks Master List</h4>
                  <span class="text-[10px] text-amber-800 font-medium">Official academic curriculum titles for current academic year</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-white text-amber-900 border border-amber-200 shadow-2xs"
                      x-text="bookNoteList.books.length + ' Titles • ' + bookNoteList.books.reduce((acc,b) => acc + (b.quantity || 1), 0) + ' Units'"></span>
              </div>
            </div>

            <div class="p-3.5 curriculum-scroll"
                 :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 260px; overflow-y: auto;'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                <template x-for="book in bookNoteList.books" :key="book.id">
                  <div class="p-2.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-amber-50/30 hover:border-amber-300 transition-all flex items-center justify-between gap-2.5">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-bold text-slate-800 leading-snug" x-text="book.name"></p>
                      <span class="font-mono text-[10px] text-slate-400 font-medium" x-text="book.sku"></span>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-amber-100/80 text-amber-900 border border-amber-200 shrink-0">
                      Qty: <span x-text="book.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>
            </div>
          </div>

          {{-- VIEW MODE 3: Dedicated Notebooks Tab (Multi-Column Compact Grid) --}}
          <div x-show="curriculumTab === 'notes'" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="p-3.5 px-4 bg-indigo-50/70 border-b border-indigo-100 flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">📝</div>
                <div>
                  <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wide">Notebooks &amp; Workbooks Master List</h4>
                  <span class="text-[10px] text-indigo-800 font-medium">Prescribed exercise books, activity diaries and graph workbooks</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-white text-indigo-900 border border-indigo-200 shadow-2xs"
                      x-text="bookNoteList.notes.length + ' Items • ' + bookNoteList.notes.reduce((acc,n) => acc + (n.quantity || 1), 0) + ' Units'"></span>
              </div>
            </div>

            <div class="p-3.5 curriculum-scroll"
                 :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 260px; overflow-y: auto;'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                <template x-for="note in bookNoteList.notes" :key="note.id">
                  <div class="p-2.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-indigo-50/30 hover:border-indigo-300 transition-all flex items-center justify-between gap-2.5">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-bold text-slate-800 leading-snug" x-text="note.name"></p>
                      <span class="font-mono text-[10px] text-slate-400 font-medium" x-text="note.sku"></span>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-100/80 text-indigo-900 border border-indigo-200 shrink-0">
                      Qty: <span x-text="note.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>
            </div>
          </div>

          {{-- VIEW MODE 4: Dedicated Uniforms Tab (Multi-Column Compact Grid) --}}
          <div x-show="curriculumTab === 'uniforms'" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="p-3.5 px-4 bg-teal-50/70 border-b border-teal-100 flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs">👔</div>
                <div>
                  <h4 class="text-xs font-black text-teal-950 uppercase tracking-wide"
                      x-text="(selectedGender || '').toLowerCase() === 'female' ? 'Girls School Uniform Master Kit' : ((selectedGender || '').toLowerCase() === 'male' ? 'Boys School Uniform Master Kit' : 'School Uniform Kit')"></h4>
                  <span class="text-[10px] text-teal-800 font-medium">Prescribed standard uniform apparel, shirts, pants/skirts and sportswear</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-white text-teal-900 border border-teal-200 shadow-2xs"
                      x-text="(bookNoteList.uniforms || []).length + ' Items • ' + (bookNoteList.uniforms || []).reduce((acc,u) => acc + (u.quantity || 1), 0) + ' Units'"></span>
              </div>
            </div>

            <div class="p-3.5 curriculum-scroll"
                 :style="curriculumExpanded ? 'max-height: none;' : 'max-height: 260px; overflow-y: auto;'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                <template x-for="uniform in (bookNoteList.uniforms || [])" :key="uniform.sku">
                  <div class="p-2.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-teal-50/30 hover:border-teal-300 transition-all flex items-center justify-between gap-2.5">
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-bold text-slate-800 leading-snug" x-text="uniform.name"></p>
                      <span class="font-mono text-[10px] text-slate-400 font-medium" x-text="uniform.sku"></span>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-teal-100/80 text-teal-900 border border-teal-200 shrink-0">
                      Qty: <span x-text="uniform.quantity || 1"></span>
                    </span>
                  </div>
                </template>
              </div>
            </div>
          </div>

        </div>

        {{-- Footer Reassurance Note --}}
        <div x-show="!bookNoteLoading && (bookNoteList.books.length > 0 || bookNoteList.notes.length > 0)"
             class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 font-medium">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
            <span>Statutory curriculum titles, exercise notebooks and uniforms above are automatically recorded upon admission submission.</span>
          </div>
          <span class="font-bold text-slate-700 shrink-0">Master Curriculum Standard</span>
        </div>

        {{-- Language & Curriculum Notes (Clean & Minimal) --}}
        <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-xs">
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-slate-700">Second Language Textbook</label>
                <span x-show="secondLanguage === 'Other'" class="text-[10px] font-bold text-[#8C2826]">Custom</span>
              </div>
              <select x-model="secondLanguage"
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="Tamil">Tamil (State Board / CBSE)</option>
                <option value="Hindi">Hindi (Course A / B)</option>
                <option value="French">French (Foreign Lang)</option>
                <option value="Sanskrit">Sanskrit (Classical)</option>
                <option value="Other">Other (Custom Language Book)</option>
              </select>
              <div x-show="secondLanguage === 'Other'" x-transition class="mt-1.5">
                <input type="text" x-model="customSecondLanguage" placeholder="Enter custom language..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
              </div>
              <input type="hidden" name="second_language" :value="effectiveSecondLanguage">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Textbook Curriculum Pack</label>
              <select name="textbook_pack" x-model="textbookPack"
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-semibold text-slate-700 bg-white">
                <option value="CBSE Standard NCERT Package">CBSE Standard NCERT Package</option>
                <option value="State Board Textbook Pack">State Board Textbook Pack</option>
                <option value="Integrated NEET/JEE Modules Pack">Integrated NEET/JEE Modules Pack</option>
                <option value="Foundation Workbook Package">Foundation Workbook Package</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Textbook &amp; Kit Notes</label>
              <input type="text" name="textbook_notes" x-model="textbookNotes"
                     placeholder="e.g. Full cover set &amp; lab manuals included"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-xs font-medium text-slate-700 bg-white">
          </div>
        </div>
      </div>

      {{-- Section 3.2: Fee Concessions & Scholarships --}}
      <div class="pt-8 border-t border-slate-100 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Fee Concessions &amp; Scholarships</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Staff Kid, Academic Topper, Sports Quota, Sibling, Single Shot Payment</p>
          </div>
          <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 shadow-2xs">Fee Concessions</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Concession Category</label>
            <select name="concession_type" x-model="concessionType"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-xs font-bold text-slate-800 transition">
              @foreach($concessionTypes as $k => $label)
                <option value="{{ $k }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Discount Amount (₹)</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-emerald-600 select-none">₹</span>
              <input type="number" step="any" min="0" id="concession_amount" name="concession_amount"
                     x-model="concessionAmountInput"
                     @input="userHasEditedConcession = true; isPaymentVerified = false;"
                     placeholder="0.00"
                     class="w-full pl-8 pr-4 py-3 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-bold transition text-emerald-700 tabular-nums bg-white outline-none">
            </div>
            <p class="text-[10px] text-slate-400 mt-1 font-medium">Freely editable; defaults to category standard discount.</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Concession Approval Remarks</label>
            <input type="text" name="concession_remarks" x-model="concessionRemarks"
                   placeholder="e.g. Approved by Principal / Correspondent"
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-xs font-medium transition text-slate-800">
          </div>
        </div>

        {{-- Concession Notice Pill --}}
        <div x-show="concessionType !== 'none'" x-transition class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs shadow-2xs">
          <span class="text-emerald-800 font-medium">
            Concession Applied: <strong class="capitalize font-bold" x-text="concessionType.replace('_', ' ')"></strong>
          </span>
          <span class="font-extrabold text-emerald-700 text-sm tabular-nums" x-text="'- ' + formatMoney(concessionAmount)"></span>
        </div>
      </div>

      {{-- Section 3.3: Student Documents & Certificate Submission --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Student Documents &amp; Certificates</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Upload certificate scans now at the desk, OR generate a self-service QR code for parent to upload from home</p>
          </div>
          
          {{-- Mode Selector Tabs --}}
          <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl shrink-0">
            <button type="button" @click="docUploadMode = 'desk'"
                    :class="docUploadMode === 'desk' ? 'bg-white text-[#8C2826] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              <span>Upload at Desk</span>
            </button>
            <button type="button" @click="docUploadMode = 'qr_home'"
                    :class="docUploadMode === 'qr_home' ? 'bg-white text-[#8C2826] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              <span>Scan QR from Home</span>
            </button>
          </div>
        </div>

        {{-- Mode A: Direct Document Uploads Now --}}
        <div x-show="docUploadMode === 'desk'" x-transition class="space-y-4">
          <div class="p-3.5 bg-[#FFF5F5] rounded-2xl border border-[#FECACA] flex items-center gap-2.5 text-xs text-[#5C1210]">
            <svg class="w-4 h-4 text-[#8C2826] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Attach digital scans or photos directly (PDF, JPG, PNG &bull; max 5MB each). Verified records are stored securely in student dossier.</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Birth Certificate --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Birth Certificate</span>
                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Mandatory</span>
              </div>
              <div x-show="!uploadedDocs.doc_birth_certificate">
                <input type="file" id="doc_birth_certificate" name="doc_birth_certificate" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_birth_certificate', 'Birth Certificate')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_birth_certificate" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_birth_certificate?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_birth_certificate?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_birth_certificate')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_birth_certificate')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            {{-- Student Aadhaar Card --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Student Aadhaar Card</span>
                <span class="text-[10px] font-bold text-[#8C2826] bg-[#FFF5F5] px-2 py-0.5 rounded border border-[#FECACA]">Mandatory</span>
              </div>
              <div x-show="!uploadedDocs.doc_aadhaar">
                <input type="file" id="doc_aadhaar" name="doc_aadhaar" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_aadhaar', 'Student Aadhaar Card')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_aadhaar" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_aadhaar?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_aadhaar?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_aadhaar')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_aadhaar')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            {{-- Community / Caste Certificate --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Community Certificate</span>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">If Applicable</span>
              </div>
              <div x-show="!uploadedDocs.doc_caste">
                <input type="file" id="doc_caste" name="doc_caste" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_caste', 'Community Certificate')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_caste" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_caste?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_caste?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_caste')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_caste')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            {{-- Transfer Certificate (TC) --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Transfer Certificate (TC)</span>
                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">Std 1 &amp; Above</span>
              </div>
              <div x-show="!uploadedDocs.doc_tc">
                <input type="file" id="doc_tc" name="doc_tc" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_tc', 'Transfer Certificate (TC)')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_tc" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_tc?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_tc?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_tc')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_tc')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            {{-- Previous Marksheet --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Previous Marksheet / Progress Card</span>
                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">Academic</span>
              </div>
              <div x-show="!uploadedDocs.doc_marksheet">
                <input type="file" id="doc_marksheet" name="doc_marksheet" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_marksheet', 'Previous Marksheet / Progress Card')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_marksheet" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_marksheet?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_marksheet?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_marksheet')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_marksheet')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>

            {{-- Parent ID Proof --}}
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800">Parent PAN / ID Proof</span>
                <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">Finance / 80G</span>
              </div>
              <div x-show="!uploadedDocs.doc_pan_id">
                <input type="file" id="doc_pan_id" name="doc_pan_id" accept=".pdf,image/*"
                       @change="handleDocChange($event, 'doc_pan_id', 'Parent PAN / ID Proof')"
                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-[#8C2826] file:text-white hover:file:bg-[#731E1C] cursor-pointer">
              </div>
              <div x-show="uploadedDocs.doc_pan_id" class="flex items-center justify-between gap-2 p-2 rounded-xl bg-white border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">✓</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate" x-text="uploadedDocs.doc_pan_id?.name"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="uploadedDocs.doc_pan_id?.size"></p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                  <button type="button" @click="viewDoc('doc_pan_id')"
                          class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>
                  <button type="button" @click="clearDoc('doc_pan_id')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs transition cursor-pointer" title="Remove file">
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Mode B: Scan QR Code from Home (Upload Later) --}}
        <div x-show="docUploadMode === 'qr_home'" x-transition class="p-6 rounded-2xl bg-slate-50 border border-slate-200 text-slate-800 space-y-4 shadow-xs">
          <div class="flex flex-col sm:flex-row items-center gap-5">
            <div class="w-16 h-16 bg-white p-2 rounded-2xl shrink-0 flex flex-col items-center justify-center text-slate-700 border border-slate-200 shadow-2xs">
              <svg class="w-10 h-10 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div class="space-y-1 text-center sm:text-left">
              <div class="flex items-center justify-center sm:justify-start gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700 px-2 py-0.5 rounded">
                  Self-Service Digital Upload
                </span>
              </div>
              <h4 class="text-sm font-bold text-slate-900">Upload Documents Later via Parent QR Code</h4>
              <p class="text-xs text-slate-600 leading-relaxed font-normal">
                If physical certificates are not immediately available at the desk, enrollment proceeds smoothly. A unique QR code and secure link will be provided on the admission receipt for the parent to submit digital copies from home.
              </p>
            </div>
          </div>
        </div>

        {{-- Physical Verification Checklist --}}
        <div class="space-y-3 pt-3 border-t border-slate-100">
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Physical Documents Received at Desk</span>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 pt-1">
            @foreach($officialDocChecklist as $docKey => $docTitle)
              <label class="p-3.5 rounded-2xl border border-slate-200 hover:bg-slate-50 flex items-center gap-3 cursor-pointer transition select-none bg-white shadow-2xs">
                <input type="checkbox" name="documents_submitted[]" value="{{ $docKey }}"
                       class="w-4 h-4 text-[#8C2826] rounded border-slate-300 focus:ring-[#8C2826] cursor-pointer">
                <span class="text-xs font-bold text-slate-800 leading-snug">{{ $docTitle }}</span>
              </label>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Section 3.4: Comprehensive Fee Breakdown & Net Grand Total --}}
      <div class="pt-8 border-t border-slate-100 space-y-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fee Structure &amp; Grand Total Breakdown</h3>

        {{-- Official 2026-2027 Fees Breakdown Table --}}
        <div x-show="selectedClassId" x-transition class="space-y-3">
          <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 text-xs space-y-3 font-medium">
            <div class="flex justify-between items-center text-slate-700">
              <span class="font-bold flex items-center gap-2">
                <span>April I Term Fee</span>
                <span class="text-[10px] text-slate-400 font-medium">(Due: 01.04.2026)</span>
              </span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(activeFeeSchedule?.term1_fee)"></span>
            </div>
            <div class="flex justify-between items-center text-slate-700">
              <span class="font-bold flex items-center gap-2">
                <span>Aug II Term Fee</span>
                <span class="text-[10px] text-slate-400 font-medium">(Due: 05.08.2026)</span>
              </span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(activeFeeSchedule?.term2_fee)"></span>
            </div>
            <div class="flex justify-between items-center text-slate-700">
              <span class="font-bold flex items-center gap-2">
                <span>Dec III Term Fee</span>
                <span class="text-[10px] text-slate-400 font-medium">(Due: 05.12.2026)</span>
              </span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(activeFeeSchedule?.term3_fee)"></span>
            </div>
            <div class="flex justify-between items-center text-purple-900 pt-2 border-t border-slate-200/70" x-show="activeFeeSchedule?.material_fee > 0">
              <span class="font-bold flex items-center gap-2">
                <span>Grade X Material Fee</span>
                <span class="text-[10px] text-purple-600 font-medium">(Due: 10.01.2026)</span>
              </span>
              <span class="font-bold text-purple-900 tabular-nums" x-text="formatMoney(activeFeeSchedule?.material_fee)"></span>
            </div>
            <div class="flex justify-between items-center text-[#380E0D] pt-2 border-t border-slate-200/70" x-show="activeFeeSchedule?.admission_fee > 0">
              <div class="flex items-center gap-2">
                <input type="checkbox" id="inc_adm_fee" x-model="includeAdmissionFee" class="w-4 h-4 text-[#8C2826] rounded">
                <label for="inc_adm_fee" class="cursor-pointer font-bold select-none">
                  Admission Fee (New Enrollment Package)
                </label>
              </div>
              <span class="font-bold text-[#8C2826] tabular-nums" x-text="formatMoney(admissionFeeTotal)"></span>
            </div>
            <div class="flex justify-between items-center text-amber-800 pt-2 border-t border-slate-200/70" x-show="aspRequired">
              <span>After School Program (ASP) Fee</span>
              <span class="font-bold text-amber-900 tabular-nums" x-text="formatMoney(aspFeeTotal)"></span>
            </div>
            <div class="flex justify-between items-center text-[#5C1210] pt-2 border-t border-slate-200/70" x-show="transportRequired && transportFee > 0">
              <span>School Transport Bus Facility Fee</span>
              <span class="font-bold text-[#8C2826] tabular-nums" x-text="formatMoney(transportFee)"></span>
            </div>
            <div class="flex justify-between items-center text-slate-700 pt-2 border-t border-slate-200/70" x-show="standardKitFeeTotal > 0">
              <span>Standard Admission Kit &amp; Textbooks</span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(standardKitFeeTotal)"></span>
            </div>
            <div class="flex justify-between items-center text-slate-700 pt-2 border-t border-slate-200/70" x-show="additionalInventoryFeeTotal > 0">
              <span>Additional Inventory Items</span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(additionalInventoryFeeTotal)"></span>
            </div>
            <div class="flex justify-between items-center text-slate-700 pt-2 border-t border-slate-200/70" x-show="customKitFeeTotal > 0">
              <span>Custom Kit &amp; Specialized Textbooks Fee</span>
              <span class="font-bold text-slate-900 tabular-nums" x-text="formatMoney(customKitFeeTotal)"></span>
            </div>
            <div class="flex justify-between items-center text-emerald-700 pt-2 border-t border-slate-200/70 font-bold" x-show="concessionAmount > 0">
              <span>Concession Discount (<span class="capitalize" x-text="concessionType.replace('_', ' ')"></span>)</span>
              <span class="font-extrabold tabular-nums" x-text="'- ' + formatMoney(concessionAmount)"></span>
            </div>
          </div>
        </div>

        {{-- Executive Grand Total Highlight Banner --}}
        <div class="bg-[#FFF5F5] border-2 border-[#FECACA] rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
          <div>
            <span class="text-[11px] font-black text-[#8C2826] uppercase tracking-widest bg-white border border-[#FECACA] px-3 py-1 rounded-full shadow-2xs">Official Fee Summary</span>
            <h3 class="text-base sm:text-lg font-black text-[#380E0D] uppercase tracking-tight mt-2">NET GRAND TOTAL ADMISSION FEE</h3>
            <p class="text-xs text-[#8C2826] font-medium mt-0.5">Academic Tuition + Materials + Facilities + Admission Kit (After Concessions)</p>
          </div>
          <div class="text-left sm:text-right">
            <span class="text-3xl sm:text-4xl font-black text-[#8C2826] tabular-nums tracking-tight block" x-text="formatMoney(grandTotal)"></span>
            <span class="text-[11px] text-slate-500 font-semibold" x-text="selectedClassName ? ('Annual Schedule for Class ' + selectedClassName) : 'Select Class'"></span>
          </div>
        </div>

        {{-- Payment Terms Selector --}}
        <div class="space-y-4 pt-2">
          <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">Payment Terms Selection</label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <label class="p-5 rounded-2xl border transition cursor-pointer flex flex-col justify-between space-y-3 select-none"
                   :class="paymentTerms === 'single' ? 'bg-[#FFF5F5] border-[#8C2826] ring-2 ring-[#FEE2E2] shadow-xs' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100'">
              <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-900">Single Payment</span>
                <input type="radio" name="payment_terms" value="single" x-model="paymentTerms" class="w-4 h-4 text-[#8C2826] focus:ring-[#8C2826]">
              </div>
              <p class="text-[11px] text-slate-500 font-medium">100% annual fee paid at admission</p>
              <p class="text-sm font-extrabold text-[#8C2826] tabular-nums" x-text="formatMoney(grandTotal)"></p>
            </label>

            <label class="p-5 rounded-2xl border transition cursor-pointer flex flex-col justify-between space-y-3 select-none"
                   :class="paymentTerms === '2_terms' ? 'bg-[#FFF5F5] border-[#8C2826] ring-2 ring-[#FEE2E2] shadow-xs' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100'">
              <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-900">2 Terms</span>
                <input type="radio" name="payment_terms" value="2_terms" x-model="paymentTerms" class="w-4 h-4 text-[#8C2826] focus:ring-[#8C2826]">
              </div>
              <p class="text-[11px] text-slate-500 font-medium">100% Kit + 50% Term Fee + 50% Transport in T1, 50% in T2</p>
              <p class="text-sm font-extrabold text-[#8C2826] tabular-nums" x-text="formatMoney(twoTermsT1) + ' (T1) &bull; ' + formatMoney(twoTermsT2) + ' (T2)'"></p>
            </label>

            <label class="p-5 rounded-2xl border transition cursor-pointer flex flex-col justify-between space-y-3 select-none"
                   :class="paymentTerms === '3_terms' ? 'bg-[#FFF5F5] border-[#8C2826] ring-2 ring-[#FEE2E2] shadow-xs' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100'">
              <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-900">3 Terms</span>
                <input type="radio" name="payment_terms" value="3_terms" x-model="paymentTerms" class="w-4 h-4 text-[#8C2826] focus:ring-[#8C2826]">
              </div>
              <p class="text-[11px] text-slate-500 font-medium">100% Kit + T1 Fee + 50% Transport, T2 Fee + 50% Transport, T3 Fee</p>
              <p class="text-sm font-extrabold text-[#8C2826] tabular-nums" x-text="formatMoney(threeTermsT1) + ' (T1) &bull; ' + formatMoney(threeTermsT2) + ' (T2) &bull; ' + formatMoney(threeTermsT3) + ' (T3)'"></p>
            </label>
          </div>
        </div>

        {{-- Dynamic Schedule Breakdown Table --}}
        <div class="space-y-3 pt-2">
          <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">Term Payment Schedule &amp; Due Dates</label>
          <div class="overflow-hidden border border-slate-200/80 rounded-2xl shadow-2xs">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-bold uppercase tracking-wider text-[10px]">
                <tr>
                  <th class="py-3 px-4">Term</th>
                  <th class="py-3 px-4 text-right">Amount</th>
                  <th class="py-3 px-4">Due Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr>
                  <td class="py-3.5 px-4 font-bold text-slate-900">Term 1 (At Admission)</td>
                  <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 tabular-nums" x-text="formatMoney(term1Amount)"></td>
                  <td class="py-3.5 px-4 text-slate-500 font-semibold" x-text="paymentDate"></td>
                </tr>
                <tr x-show="paymentTerms === '2_terms' || paymentTerms === '3_terms'">
                  <td class="py-3.5 px-4 font-bold text-slate-900">Term 2</td>
                  <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 tabular-nums" x-text="formatMoney(term2Amount)"></td>
                  <td class="py-3.5 px-4">
                    <input type="date" name="term_2_due_date" x-model="term2DueDate" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 bg-white">
                  </td>
                </tr>
                <tr x-show="paymentTerms === '3_terms'">
                  <td class="py-3.5 px-4 font-bold text-slate-900">Term 3</td>
                  <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 tabular-nums" x-text="formatMoney(term3Amount)"></td>
                  <td class="py-3.5 px-4">
                    <input type="date" name="term_3_due_date" x-model="term3DueDate" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 bg-white">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- Section 3.6: Payment Mode & Collection Account --}}
        <div class="space-y-5 pt-4">
          <div>
            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
              Payment Destination Account &amp; Mode <span class="text-rose-500">*</span>
            </label>
            <p class="text-xs text-slate-500 font-medium">Select whether the fee was paid via UPI QR or physical cash (automatically added to the respective box in Account Management):</p>
          </div>

          {{-- Hidden fields for form submission --}}
          <input type="hidden" name="payment_mode" :value="paymentMode">
          <input type="hidden" name="payment_account" :value="paymentAccount">

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Option 1: UPI Account --}}
            <div @click="setAccount('upi', 'UPI')"
                 class="p-5 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between relative select-none"
                 :class="paymentAccount === 'upi' ? 'border-purple-500 bg-purple-50/70 ring-2 ring-purple-100 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'">
              <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition"
                     :class="paymentAccount === 'upi' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 text-purple-600'">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span x-show="paymentAccount === 'upi'" class="text-[10px] font-extrabold uppercase tracking-wide text-purple-700 bg-purple-100 px-2.5 py-0.5 rounded-full border border-purple-200">Selected</span>
              </div>
              <div class="mt-4">
                <h4 class="text-xs font-bold text-slate-900">UPI Digital Account</h4>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">PhonePe, GPay, Paytm &amp; QR</p>
                <span class="text-[10px] text-purple-700 font-bold block mt-2.5">&rarr; Deposited to UPI Ledger</span>
              </div>
            </div>

            {{-- Option 2: Cash Box 1 (Front Office) --}}
            <div @click="setAccount('cash_box_1', 'Cash')"
                 class="p-5 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between relative select-none"
                 :class="paymentAccount === 'cash_box_1' ? 'border-emerald-500 bg-emerald-50/70 ring-2 ring-emerald-100 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'">
              <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition"
                     :class="paymentAccount === 'cash_box_1' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-600'">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span x-show="paymentAccount === 'cash_box_1'" class="text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-200">Selected</span>
              </div>
              <div class="mt-4">
                <h4 class="text-xs font-bold text-slate-900">Front Office Cash (Box 1)</h4>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Front Desk &amp; Admission Counter</p>
                <span class="text-[10px] text-emerald-700 font-bold block mt-2.5">&rarr; Deposited to Cash Box 1</span>
              </div>
            </div>

            {{-- Option 3: Cash Box 2 (Accounts Dept) --}}
            <div @click="setAccount('cash_box_2', 'Cash')"
                 class="p-5 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between relative select-none"
                 :class="paymentAccount === 'cash_box_2' ? 'border-[#8C2826] bg-[#FFF5F5] ring-2 ring-[#FEE2E2] shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'">
              <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition"
                     :class="paymentAccount === 'cash_box_2' ? 'bg-[#8C2826] text-white shadow-xs' : 'bg-[#FFF5F5] text-[#8C2826]'">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span x-show="paymentAccount === 'cash_box_2'" class="text-[10px] font-extrabold uppercase tracking-wide text-[#8C2826] bg-[#FFF5F5] px-2.5 py-0.5 rounded-full border border-[#FECACA]">Selected</span>
              </div>
              <div class="mt-4">
                <h4 class="text-xs font-bold text-slate-900">Accounts Vault (Box 2)</h4>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Accounts Department Vault</p>
                <span class="text-[10px] text-[#8C2826] font-bold block mt-2.5">&rarr; Deposited to Cash Box 2</span>
              </div>
            </div>
          </div>

          {{-- UPI UTR / Reference ID Field (Visible when UPI is active) --}}
          <div x-show="paymentAccount === 'upi'" x-transition class="p-5 bg-purple-50/70 rounded-2xl border border-purple-200/80 space-y-2">
            <label class="block text-xs font-bold text-slate-800">UPI Transaction ID / UTR Number <span class="text-slate-400 font-normal">(Optional)</span></label>
            <input type="text" id="transaction_id" name="transaction_id" x-model="upiRefNo" placeholder="e.g. 202612345678 or UPI UTR No."
                   class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition outline-none tracking-wider">
            <p class="text-[11px] text-slate-500 font-medium">This transaction ID will be stored with the student fee receipt and matched in Account Management.</p>
          </div>

          {{-- Payment Date & Amount Row --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-1">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-2">Payment Date <span class="text-rose-500">*</span></label>
              <input type="date" id="payment_date" name="payment_date" x-model="paymentDate" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition text-slate-700 bg-white">
            </div>

            <div>
              <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                <label class="block text-xs font-bold text-slate-700">Amount Collected Today (₹) <span class="text-rose-500">*</span></label>
                {{-- Quick Presets --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                  <button type="button" @click="setAmountPreset(admissionFeeTotal > 0 ? admissionFeeTotal : Math.round(grandTotal * 0.2))"
                          class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition cursor-pointer">
                    🎓 Admission Only
                  </button>
                  <button type="button" @click="setAmountPreset(term1Amount)"
                          class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition cursor-pointer">
                    📅 Term 1 Fee
                  </button>
                  <button type="button" @click="setAmountPreset(grandTotal)"
                          class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition cursor-pointer">
                    🏆 Full Fee
                  </button>
                </div>
              </div>

              {{-- Amount Input + Explicit Verify Button --}}
              <div class="flex items-stretch gap-2.5">
                <div class="relative flex-1">
                  <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-xs font-bold text-slate-400 select-none">₹</span>
                  <input type="number" step="any" min="0" id="amount_collected" name="amount_collected" required
                         x-model="userAmountCollected"
                         @input="onAmountCollectedChanged($event.target.value)"
                         placeholder="0.00"
                         class="w-full pl-8 pr-4 py-3 rounded-xl border @error('amount_collected') border-rose-400 @else border-slate-200 @enderror focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-bold transition text-slate-900 tabular-nums bg-white outline-none">
                </div>
                <button type="button" @click="verifyPayment()"
                        :class="isPaymentVerified ? 'bg-emerald-600 hover:bg-emerald-700 text-white ring-2 ring-emerald-200 shadow-sm' : 'bg-[#8C2826] hover:bg-[#731E1C] text-white shadow-sm'"
                        class="px-4 py-3 rounded-xl font-bold text-xs flex items-center gap-1.5 transition shrink-0 cursor-pointer">
                  <template x-if="isPaymentVerified">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      <span>Verified</span>
                    </span>
                  </template>
                  <template x-if="!isPaymentVerified">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span>Verify</span>
                    </span>
                  </template>
                </button>
              </div>
              @error('amount_collected') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
              <p id="amount_collected_client_err" class="text-xs text-rose-500 mt-1 font-semibold hidden"></p>

              {{-- Verified Status Ribbon --}}
              <div x-show="isPaymentVerified" x-transition class="mt-2.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="w-5 h-5 rounded-full bg-emerald-600 text-white font-black text-[11px] flex items-center justify-center shrink-0">✓</span>
                  <div class="truncate">
                    <span>Payment amount <strong class="font-extrabold" x-text="formatMoney(amountCollected)"></strong> is <strong class="text-emerald-700">Verified</strong></span>
                    <span class="text-[10px] text-emerald-700 block sm:inline sm:ml-1 font-medium" x-text="'by ' + verifiedBy + ' • ' + verifiedAt"></span>
                  </div>
                </div>
                <button type="button" @click="verificationModalOpen = true" class="text-xs font-bold text-emerald-800 hover:text-emerald-950 underline shrink-0 ml-3 cursor-pointer">
                  Audit History
                </button>
              </div>

              {{-- Live Waterfall Notice --}}
              <div x-show="amountCollected > term1Amount && amountCollected < grandTotal && paymentTerms !== 'single'" class="mt-2 p-2.5 rounded-xl bg-blue-50/80 border border-blue-200/80 text-[11px] text-blue-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Settle Term 1 and automatically adjust <strong class="font-bold text-blue-900" x-text="formatMoney(amountCollected - term1Amount)"></strong> towards upcoming terms sequentially.</span>
              </div>

              <div x-show="excessAmount > 0" class="mt-2 p-2.5 rounded-xl bg-purple-50/80 border border-purple-200/80 text-[11px] text-purple-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Full fees settled! <strong class="font-bold text-purple-900" x-text="formatMoney(excessAmount)"></strong> surplus will be credited as an advance credit balance.</span>
              </div>
            </div>
          </div>
        </div>

        {{-- Live Collection Status Summary Tiles (5-Tile Comprehensive Audit Matrix) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 p-5 bg-slate-50 rounded-2xl border border-slate-200/80 shadow-2xs">
          <div>
            <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Collected Today</span>
            <p class="text-xl sm:text-2xl font-black text-emerald-700 tabular-nums mt-1" x-text="formatMoney(amountCollected)"></p>
          </div>
          <div>
            <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">Pending Amount</span>
            <p class="text-xl sm:text-2xl font-black text-amber-600 tabular-nums mt-1" x-text="formatMoney(pendingAmount)"></p>
          </div>
          <div>
            <span class="text-[10px] font-bold text-purple-800 uppercase tracking-wider">Excess / Advance</span>
            <p class="text-xl sm:text-2xl font-black tabular-nums mt-1"
               :class="excessAmount > 0 ? 'text-purple-700 font-black' : 'text-slate-400 font-medium'"
               x-text="excessAmount > 0 ? formatMoney(excessAmount) : '₹0'"></p>
          </div>
          <div>
            <span class="text-[10px] font-bold text-[#380E0D] uppercase tracking-wider">Payment Status</span>
            <p class="text-sm sm:text-base font-extrabold mt-1.5"
               :class="paymentStatus === 'Paid' ? 'text-emerald-600' : (paymentStatus === 'Partially Paid' ? 'text-amber-600' : 'text-slate-600')"
               x-text="excessAmount > 0 ? 'Advance Paid' : paymentStatus">
            </p>
          </div>
          <div>
            <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider">Verification Status</span>
            <div class="mt-1.5">
              <template x-if="isPaymentVerified">
                <span class="inline-flex items-center gap-1 text-emerald-700 font-extrabold text-sm">
                  <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  <span>Verified</span>
                </span>
              </template>
              <template x-if="!isPaymentVerified">
                <span class="inline-flex items-center gap-1.5 text-amber-700 font-bold text-xs bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  <span>Pending Verify</span>
                </span>
              </template>
            </div>
          </div>
        </div>
      </div>

      {{-- Section 3.7: Counselor Notes & Admission Sign-Off --}}
      <div class="pt-8 border-t border-slate-100 space-y-4">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Counselor Notes &amp; Special Instructions</h3>
        <textarea name="notes" rows="3"
                  placeholder="Enter any admission remarks, special accommodations, fee concession approvals, or sibling references..."
                  class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-[#8C2826] focus:ring-2 focus:ring-[#FEE2E2] text-sm font-medium transition bg-white">{{ old('notes') }}</textarea>

        {{-- Signature Box on Print --}}
        <div class="hidden print:grid grid-cols-2 gap-12 pt-8 text-xs">
          <div>
            <p class="font-bold text-slate-800">Parent / Guardian Signature</p>
            <div class="h-10 border-b border-slate-400 border-dashed"></div>
          </div>
          <div class="text-right">
            <p class="font-bold text-slate-800">Admissions Officer Signature</p>
            <div class="h-10 border-b border-slate-400 border-dashed"></div>
          </div>
        </div>
      </div>

      {{-- ── Form Action Buttons ────────────────────────────────────────── --}}
      <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 print:hidden">
        <div class="flex items-center gap-2.5 text-xs text-slate-500 font-medium">
          <svg class="w-4 h-4 text-[#8C2826] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Admission slip, student register entry, and fee receipt will be automatically generated upon enrollment.</span>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
          <a href="{{ route('admissions.index') }}" class="px-5 h-12 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold border border-slate-200 text-xs transition flex items-center justify-center shadow-2xs">
            Cancel
          </a>
          <button type="submit" :disabled="isSubmitting"
                  class="px-8 h-12 rounded-xl bg-gradient-to-r from-[#8C2826] to-[#A13431] hover:from-[#731E1C] hover:to-[#8C2826] text-white font-extrabold text-xs shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="!isSubmitting">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Submit &amp; Enroll Student</span>
              </span>
            </template>
            <template x-if="isSubmitting">
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span>Saving Admission Record...</span>
              </span>
            </template>
          </button>
        </div>
      </div>
    </div>
  </form>

  {{-- ── Universal File Preview Modal (Photos & Documents remain hidden by default) ── --}}
  <div x-show="previewModalOpen" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @keydown.escape.window="closePreview()"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.away="closePreview()"
         class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
      {{-- Modal Header --}}
      <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </div>
          <div>
            <h4 class="text-xs font-bold text-slate-900 truncate" x-text="previewModalTitle"></h4>
            <p class="text-[10px] text-slate-400 font-medium">Uploaded file preview • Secure student record</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <a :href="previewModalUrl" target="_blank" download class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download</span>
          </a>
          <button type="button" @click="closePreview()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition cursor-pointer font-bold">
            ✕
          </button>
        </div>
      </div>

      {{-- Modal Body --}}
      <div class="p-6 overflow-auto flex-1 flex items-center justify-center bg-slate-100/50 min-h-[350px]">
        <template x-if="previewModalType === 'image'">
          <div class="max-w-full text-center">
            <img :src="previewModalUrl" class="max-h-[70vh] max-w-full rounded-2xl object-contain shadow-md mx-auto border border-slate-200" alt="Preview">
          </div>
        </template>
        <template x-if="previewModalType === 'pdf'">
          <iframe :src="previewModalUrl" class="w-full h-[70vh] rounded-2xl border border-slate-200 bg-white shadow-md"></iframe>
        </template>
      </div>

      {{-- Modal Footer --}}
      <div class="px-6 py-3.5 bg-white border-t border-slate-100 flex items-center justify-between shrink-0 text-xs text-slate-500">
        <span>Files remain hidden on the form by default to preserve cleanliness and privacy.</span>
        <button type="button" @click="closePreview()" class="px-4 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition cursor-pointer">
          Close Preview
        </button>
      </div>
    </div>
  </div>

  {{-- ── Payment Verification Audit Modal ── --}}
  <div x-show="verificationModalOpen" x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @keydown.escape.window="verificationModalOpen = false"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.away="verificationModalOpen = false"
         class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden flex flex-col">
      {{-- Modal Header --}}
      <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div>
            <h4 class="text-sm font-extrabold tracking-tight">Payment Verification Summary</h4>
            <p class="text-[11px] text-emerald-100">Official fee collection cross-check &amp; ledger audit</p>
          </div>
        </div>
        <button type="button" @click="verificationModalOpen = false" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer font-bold">
          ✕
        </button>
      </div>

      {{-- Modal Body --}}
      <div class="p-6 space-y-5 bg-white overflow-y-auto max-h-[75vh]">
        {{-- Amount Verified Highlight Card --}}
        <div class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Verified Collection Amount</span>
            <p class="text-3xl font-black text-emerald-800 tabular-nums mt-0.5" x-text="formatMoney(amountCollected)"></p>
          </div>
          <div class="text-right">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-600 text-white font-extrabold text-xs shadow-2xs">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Verified</span>
            </span>
            <p class="text-[10px] text-emerald-700 font-medium mt-1" x-text="verifiedAt"></p>
          </div>
        </div>

        {{-- Verification Audit Details Grid --}}
        <div class="space-y-3">
          <h5 class="text-xs font-extrabold text-slate-800 uppercase tracking-wide">Verification Audit Record</h5>
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] text-slate-400 font-semibold block">Verified By</span>
              <span class="font-extrabold text-slate-800" x-text="verifiedBy"></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] text-slate-400 font-semibold block">Deposit Account</span>
              <span class="font-extrabold text-slate-800 capitalize" x-text="paymentAccount === 'cash_box_1' ? 'Front Office Cash (Box 1)' : (paymentAccount === 'cash_box_2' ? 'Accounts Vault (Box 2)' : 'UPI Digital Account')"></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] text-slate-400 font-semibold block">Remaining Balance</span>
              <span class="font-extrabold text-amber-700" x-text="formatMoney(pendingAmount)"></span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] text-slate-400 font-semibold block">Grand Total Fees</span>
              <span class="font-extrabold text-slate-900" x-text="formatMoney(grandTotal)"></span>
            </div>
          </div>
        </div>

        {{-- Verification History Log --}}
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wide">Verification History (Audit Trail)</span>
            <span class="text-[10px] text-slate-400 font-semibold" x-text="verificationHistory.length + ' entries logged'"></span>
          </div>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <template x-for="(entry, idx) in verificationHistory" :key="idx">
              <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                  <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                  <div>
                    <p class="font-bold text-slate-900" x-text="entry.formattedAmount + ' verified (' + entry.mode + ')'"></p>
                    <p class="text-[10px] text-slate-400 font-medium" x-text="'Verified by ' + entry.verifier + ' • ' + entry.timestamp"></p>
                  </div>
                </div>
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Confirmed</span>
              </div>
            </template>
          </div>
        </div>
      </div>

      {{-- Modal Footer --}}
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end shrink-0">
        <button type="button" @click="verificationModalOpen = false" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition cursor-pointer">
          Done &amp; Continue
        </button>
      </div>
    </div>
  </div>
</div>


{{-- Multi-page Clean Print Styling --}}
<style>
  @media print {
    @page {
      size: A4;
      margin: 8mm 12mm;
    }
    body * {
      visibility: hidden;
    }
    form, form *, .print-card, .print-card * {
      visibility: visible;
    }
    form {
      position: absolute;
      left: 0;
      top: 0;
      width: 100% !important;
      max-width: 100% !important;
    }
    .print\:hidden {
      display: none !important;
    }
    .print-card {
      break-inside: avoid !important;
      page-break-inside: avoid !important;
      border: 1px solid #cbd5e1 !important;
      box-shadow: none !important;
      border-radius: 12px !important;
      margin-bottom: 12px !important;
      padding: 16px !important;
    }
    input, select, textarea {
      border: 1px solid #94a3b8 !important;
      background-color: #f8fafc !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      padding: 6px 10px !important;
      font-size: 11px !important;
    }
  }
</style>

<script>
  // Auto-format phone input: handles pasted numbers like +91 98765 43210 or 09876543210
  function formatPhoneInput(input) {
    if (!input) return;
    let clean = input.value.replace(/\D/g, '');
    if (clean.length === 12 && clean.startsWith('91')) {
      clean = clean.slice(2);
    } else if (clean.length === 11 && clean.startsWith('0')) {
      clean = clean.slice(1);
    }
    input.value = clean.slice(0, 10);
  }

  // Real-time phone badge updater
  function updatePhoneBadge(input, badgeId, isRequired = false) {
    const badge = document.getElementById(badgeId);
    if (!badge || !input) return;
    formatPhoneInput(input);
    const val = input.value.trim();
    const len = val.length;

    if (len === 0) {
      if (isRequired) {
        badge.innerHTML = '<span class="text-rose-500 font-bold">10 digits required *</span>';
      } else {
        badge.innerHTML = '<span class="text-slate-400">10 digits (Optional)</span>';
      }
      input.classList.remove('border-rose-400', 'border-emerald-500', 'ring-2', 'ring-rose-100', 'ring-emerald-100');
      return;
    }

    if (len < 10) {
      badge.innerHTML = `<span class="text-amber-600 font-bold">${len}/10 digits</span>`;
      input.classList.remove('border-emerald-500', 'ring-emerald-100');
    } else if (len === 10) {
      if (/^[6-9]\d{9}$/.test(val)) {
        badge.innerHTML = '<span class="text-emerald-600 font-bold flex items-center gap-1"><i class="fas fa-check-circle"></i> Valid 10-digit Mobile</span>';
        input.classList.remove('border-rose-400', 'ring-rose-100');
        input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
      } else {
        badge.innerHTML = '<span class="text-rose-600 font-bold flex items-center gap-1"><i class="fas fa-exclamation-circle"></i> Must start with 6, 7, 8, or 9</span>';
        input.classList.remove('border-emerald-500', 'ring-emerald-100');
        input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
      }
    }
  }

  // Live validator for phone inputs on blur
  function validatePhoneLive(input, errId, badgeId, isRequired = false) {
    if (!input) return true;
    formatPhoneInput(input);
    updatePhoneBadge(input, badgeId, isRequired);
    const errEl = document.getElementById(errId);
    const val = input.value.trim();

    if (!val) {
      if (isRequired) {
        if (errEl) {
          errEl.innerText = 'Mobile number is required (10 digits).';
          errEl.classList.remove('hidden');
        }
        input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
        return false;
      }
      if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      input.classList.remove('border-rose-400', 'ring-rose-100');
      return true;
    }

    if (!/^[6-9]\d{9}$/.test(val)) {
      if (errEl) {
        errEl.innerText = 'Must be a valid 10-digit Indian mobile starting with 6, 7, 8, or 9.';
        errEl.classList.remove('hidden');
      }
      input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
      return false;
    }

    if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
    input.classList.remove('border-rose-400', 'ring-rose-100');
    input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
    return true;
  }

  // Strict RFC-compliant email regex
  const RFC_EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;

  // Live RFC Email Validator
  function validateEmailLive(input, errId, isRequired = false) {
    if (!input) return true;
    const val = input.value.trim();
    const errEl = document.getElementById(errId);

    if (!val) {
      if (isRequired) {
        if (errEl) {
          errEl.innerText = 'Email address is required.';
          errEl.classList.remove('hidden');
        }
        input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
        return false;
      }
      if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      input.classList.remove('border-rose-400', 'ring-rose-100', 'border-emerald-500', 'ring-emerald-100');
      return true;
    }

    if (!RFC_EMAIL_REGEX.test(val)) {
      if (errEl) {
        errEl.innerText = 'Please enter a valid email format (e.g. name@example.com).';
        errEl.classList.remove('hidden');
      }
      input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
      input.classList.remove('border-emerald-500', 'ring-emerald-100');
      return false;
    }

    if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
    input.classList.remove('border-rose-400', 'ring-rose-100');
    input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
    return true;
  }

  function validateRequiredLive(input, errId, message) {
    if (!input) return true;
    const val = input.value.trim();
    const errEl = document.getElementById(errId);
    if (!val || val.length < 2) {
      if (errEl) { errEl.innerText = message; errEl.classList.remove('hidden'); }
      input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
      return false;
    }
    if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
    input.classList.remove('border-rose-400', 'ring-rose-100');
    return true;
  }

  function validateGenderLive(select) {
    if (!select) return;
    const errEl = document.getElementById('gender_client_err');
    if (!select.value) {
      if (errEl) { errEl.innerText = 'Please select student Gender.'; errEl.classList.remove('hidden'); }
      select.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
    } else {
      if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      select.classList.remove('border-rose-400', 'ring-rose-100');
    }
  }

  // Real-time Government Document & Unique Identity Verification
  let aadhaarCheckTimer = null;
  let isAadhaarDuplicate = false;
  let aadhaarDuplicateMsg = '';

  function checkAadhaarUniqueness(val) {
    const badge = document.getElementById('aadhaar_no_badge');
    const input = document.getElementById('aadhaar_no');
    const errEl = document.getElementById('aadhaar_no_client_err');
    if (!input || !badge) return;

    if (val.length !== 12) {
      isAadhaarDuplicate = false;
      aadhaarDuplicateMsg = '';
      if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      return;
    }

    badge.innerHTML = '<span class="text-blue-600 font-semibold flex items-center gap-1"><i class="fas fa-spinner fa-spin text-xs"></i> Checking...</span>';

    fetch(`{{ route('admissions.check-unique') }}?field=aadhaar_no&value=${encodeURIComponent(val)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
      if (input.value.trim() !== val) return;

      if (!data.available) {
        isAadhaarDuplicate = true;
        const studentInfo = data.student ? `<strong>${data.student.name}</strong> (Admn: <strong>${data.student.admission_no}</strong>, Class: ${data.student.class})` : 'another student';
        aadhaarDuplicateMsg = data.message || `Already used for another student (${data.student ? data.student.admission_no : ''})`;

        badge.innerHTML = '<span class="text-rose-700 bg-rose-100 border border-rose-300 px-2 py-0.5 rounded font-extrabold flex items-center gap-1 shadow-sm"><i class="fas fa-exclamation-circle text-rose-600"></i> Already used</span>';
        input.classList.remove('border-emerald-500', 'ring-emerald-100', 'border-slate-200');
        input.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/20');

        if (errEl) {
          errEl.innerHTML = `<span class="inline-flex items-center gap-1 text-rose-600 font-bold"><i class="fas fa-times-circle"></i> Already used for student: ${studentInfo}. Government documents cannot be reused for another admission.</span>`;
          errEl.classList.remove('hidden');
        }
      } else {
        isAadhaarDuplicate = false;
        aadhaarDuplicateMsg = '';
        badge.innerHTML = '<span class="text-emerald-700 bg-emerald-50 border border-emerald-300 px-2 py-0.5 rounded font-bold flex items-center gap-1 shadow-sm"><i class="fas fa-check-circle text-emerald-600"></i> Valid &amp; Available</span>';
        input.classList.remove('border-rose-400', 'border-rose-500', 'ring-rose-100', 'ring-rose-200', 'bg-rose-50/20');
        input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100', 'bg-emerald-50/10');
        if (errEl) {
          errEl.innerText = '';
          errEl.classList.add('hidden');
        }
      }
    })
    .catch(err => {
      console.warn('Aadhaar unique check failed:', err);
      badge.innerHTML = '<span class="text-emerald-600 font-bold flex items-center gap-1"><i class="fas fa-check-circle"></i> Valid 12-digit Aadhaar</span>';
    });
  }

  // Real-time Aadhaar badge updater
  function updateAadhaarBadge(input, badgeId) {
    const badge = document.getElementById(badgeId);
    if (!badge || !input) return;
    const val = input.value.trim();
    const len = val.length;

    if (len === 0) {
      badge.innerHTML = '<span class="text-slate-400">12 digits (Optional)</span>';
      input.classList.remove('border-rose-400', 'border-rose-500', 'border-emerald-500', 'ring-2', 'ring-rose-100', 'ring-rose-200', 'ring-emerald-100', 'bg-rose-50/20', 'bg-emerald-50/10');
      if (input.id === 'aadhaar_no') {
        isAadhaarDuplicate = false;
        aadhaarDuplicateMsg = '';
        const errEl = document.getElementById('aadhaar_no_client_err');
        if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      }
      return;
    }

    if (len < 12) {
      badge.innerHTML = `<span class="text-amber-600 font-bold">${len}/12 digits</span>`;
      input.classList.remove('border-emerald-500', 'ring-emerald-100', 'bg-emerald-50/10');
      if (input.id === 'aadhaar_no') {
        isAadhaarDuplicate = false;
        aadhaarDuplicateMsg = '';
        const errEl = document.getElementById('aadhaar_no_client_err');
        if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      }
    } else if (len === 12) {
      if (input.id === 'aadhaar_no') {
        clearTimeout(aadhaarCheckTimer);
        aadhaarCheckTimer = setTimeout(() => {
          checkAadhaarUniqueness(val);
        }, 200);
      } else {
        // Father Aadhaar: valid 12-digit (allowed to be shared across siblings)
        badge.innerHTML = '<span class="text-emerald-600 font-bold flex items-center gap-1"><i class="fas fa-check-circle"></i> Valid 12-digit Aadhaar</span>';
        input.classList.remove('border-rose-400', 'ring-rose-100');
        input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
      }
    }
  }

  // Real-time EMIS / PEN Number Uniqueness Check
  let emisCheckTimer = null;
  let isEmisDuplicate = false;
  let emisDuplicateMsg = '';

  function checkEmisUnique(input) {
    if (!input) input = document.getElementById('emis_no');
    if (!input) return;

    const val = input.value.trim();
    const badge = document.getElementById('emis_no_badge');
    const errEl = document.getElementById('emis_no_client_err');

    if (!val) {
      isEmisDuplicate = false;
      emisDuplicateMsg = '';
      if (badge) {
        badge.innerHTML = 'Optional';
        badge.className = 'text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200';
      }
      input.classList.remove('border-rose-500', 'border-emerald-500', 'ring-2', 'ring-rose-100', 'ring-emerald-100', 'bg-rose-50/20');
      if (errEl) { errEl.innerText = ''; errEl.classList.add('hidden'); }
      return;
    }

    if (badge) {
      badge.innerHTML = '<span class="text-blue-600 font-semibold flex items-center gap-1"><i class="fas fa-spinner fa-spin text-xs"></i> Checking...</span>';
      badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-md';
    }

    fetch(`{{ route('admissions.check-unique') }}?field=emis_no&value=${encodeURIComponent(val)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
      if (input.value.trim() !== val) return;

      if (!data.available) {
        isEmisDuplicate = true;
        const studentInfo = data.student ? `<strong>${data.student.name}</strong> (Admn: <strong>${data.student.admission_no}</strong>, Class: ${data.student.class})` : 'another student';
        emisDuplicateMsg = data.message || `Already used for another student (${data.student ? data.student.admission_no : ''})`;

        if (badge) {
          badge.innerHTML = '<span class="text-rose-700 bg-rose-100 border border-rose-300 px-2 py-0.5 rounded font-extrabold flex items-center gap-1 shadow-sm"><i class="fas fa-exclamation-circle text-rose-600"></i> Already used</span>';
          badge.className = 'text-[10px] font-bold';
        }
        input.classList.remove('border-emerald-500', 'ring-emerald-100', 'border-slate-200');
        input.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/20');

        if (errEl) {
          errEl.innerHTML = `<span class="inline-flex items-center gap-1 text-rose-600 font-bold"><i class="fas fa-times-circle"></i> Already used for student: ${studentInfo}. Government documents cannot be reused for another admission.</span>`;
          errEl.classList.remove('hidden');
        }
      } else {
        isEmisDuplicate = false;
        emisDuplicateMsg = '';
        if (badge) {
          badge.innerHTML = '<span class="text-emerald-700 bg-emerald-50 border border-emerald-300 px-2 py-0.5 rounded font-bold flex items-center gap-1 shadow-sm"><i class="fas fa-check-circle text-emerald-600"></i> Available</span>';
          badge.className = 'text-[10px] font-bold';
        }
        input.classList.remove('border-rose-400', 'border-rose-500', 'ring-rose-100', 'ring-rose-200', 'bg-rose-50/20');
        input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
        if (errEl) {
          errEl.innerText = '';
          errEl.classList.add('hidden');
        }
      }
    })
    .catch(err => {
      console.warn('EMIS check failed:', err);
      if (badge) {
        badge.innerHTML = 'Optional';
        badge.className = 'text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200';
      }
    });
  }

  function checkEmisUniqueDebounced(input) {
    clearTimeout(emisCheckTimer);
    emisCheckTimer = setTimeout(() => {
      checkEmisUnique(input);
    }, 400);
  }

  // Real-time Student Email Uniqueness Check (Class 9+)
  let emailCheckTimer = null;
  let isEmailDuplicate = false;
  let emailDuplicateMsg = '';

  function checkEmailUnique(input) {
    if (!input) input = document.getElementById('email');
    if (!input) return;

    const val = input.value.trim();
    const badge = document.getElementById('email_badge');
    const errEl = document.getElementById('email_client_err');

    if (!val || !RFC_EMAIL_REGEX.test(val)) {
      isEmailDuplicate = false;
      emailDuplicateMsg = '';
      return;
    }

    fetch(`{{ route('admissions.check-unique') }}?field=email&value=${encodeURIComponent(val)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
      if (input.value.trim() !== val) return;

      if (!data.available) {
        isEmailDuplicate = true;
        const studentInfo = data.student ? `<strong>${data.student.name}</strong> (Admn: <strong>${data.student.admission_no}</strong>, Class: ${data.student.class})` : 'another student';
        emailDuplicateMsg = data.message || `Email already registered to ${data.student ? data.student.name : 'another student'}`;

        if (badge) {
          badge.innerHTML = '<span class="text-rose-700 bg-rose-100 border border-rose-300 px-2 py-0.5 rounded font-extrabold flex items-center gap-1 shadow-sm"><i class="fas fa-exclamation-circle text-rose-600"></i> Already used</span>';
        }
        input.classList.remove('border-emerald-500', 'ring-emerald-100', 'border-slate-200');
        input.classList.add('border-rose-500', 'ring-2', 'ring-rose-200', 'bg-rose-50/20');

        if (errEl) {
          errEl.innerHTML = `<span class="inline-flex items-center gap-1 text-rose-600 font-bold"><i class="fas fa-times-circle"></i> Already registered to student: ${studentInfo}.</span>`;
          errEl.classList.remove('hidden');
        }
      } else {
        isEmailDuplicate = false;
        emailDuplicateMsg = '';
        if (badge) {
          badge.innerHTML = 'Optional';
        }
      }
    })
    .catch(err => {
      console.warn('Email check failed:', err);
    });
  }

  function checkEmailUniqueDebounced(input) {
    clearTimeout(emailCheckTimer);
    emailCheckTimer = setTimeout(() => {
      checkEmailUnique(input);
    }, 400);
  }

  // Real-time Pincode badge updater
  function updatePincodeBadge(input, badgeId) {
    const badge = document.getElementById(badgeId);
    if (!badge || !input) return;
    const val = input.value.trim();
    const len = val.length;

    if (len === 0) {
      badge.innerHTML = '<span class="text-slate-400">6 digits (Optional)</span>';
      input.classList.remove('border-rose-400', 'border-emerald-500', 'ring-2', 'ring-rose-100', 'ring-emerald-100');
      return;
    }

    if (len < 6) {
      badge.innerHTML = `<span class="text-amber-600 font-bold">${len}/6 digits</span>`;
      input.classList.remove('border-emerald-500', 'ring-emerald-100');
    } else if (len === 6) {
      if (/^[1-9]\d{5}$/.test(val)) {
        badge.innerHTML = '<span class="text-emerald-600 font-bold flex items-center gap-1"><i class="fas fa-check-circle"></i> Valid 6-digit PIN</span>';
        input.classList.remove('border-rose-400', 'ring-rose-100');
        input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
      } else {
        badge.innerHTML = '<span class="text-rose-500 font-bold">Invalid PIN (cannot start with 0)</span>';
        input.classList.remove('border-emerald-500', 'ring-emerald-100');
        input.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
      }
    }
  }

  // Initialize badges on page load if old inputs exist
  document.addEventListener('DOMContentLoaded', function() {
    const parentMobile = document.getElementById('parent_mobile');
    if (parentMobile && parentMobile.value) updatePhoneBadge(parentMobile, 'parent_mobile_badge', true);

    const motherMobile = document.getElementById('mother_mobile');
    if (motherMobile && motherMobile.value) updatePhoneBadge(motherMobile, 'mother_mobile_badge', false);

    const guardianMobile = document.getElementById('guardian_mobile');
    if (guardianMobile && guardianMobile.value) updatePhoneBadge(guardianMobile, 'guardian_mobile_badge', false);

    const aadhaarNo = document.getElementById('aadhaar_no');
    if (aadhaarNo && aadhaarNo.value) {
      updateAadhaarBadge(aadhaarNo, 'aadhaar_no_badge');
      if (aadhaarNo.value.trim().length === 12) {
        checkAadhaarUniqueness(aadhaarNo.value.trim());
      }
    }

    const fatherAadhaar = document.getElementById('father_aadhaar');
    if (fatherAadhaar && fatherAadhaar.value) updateAadhaarBadge(fatherAadhaar, 'father_aadhaar_badge');

    const emisNo = document.getElementById('emis_no');
    if (emisNo && emisNo.value) checkEmisUnique(emisNo);

    const email = document.getElementById('email');
    if (email && email.value) checkEmailUnique(email);

    const pincode = document.getElementById('pincode');
    if (pincode && pincode.value) updatePincodeBadge(pincode, 'pincode_badge');

    // Comprehensive client-side form validation interceptor
    const form = document.getElementById('admissionCreateForm');
    if (form) {
      form.addEventListener('submit', function(e) {
        let errors = [];
        let firstInvalidElement = null;

        // Reset previous client error highlights
        document.querySelectorAll('[id$="_client_err"]').forEach(el => {
          el.innerText = '';
          el.classList.add('hidden');
        });
        document.querySelectorAll('.border-rose-400').forEach(el => {
          el.classList.remove('border-rose-400', 'ring-2', 'ring-rose-100');
        });

        function markInvalid(inputEl, errElId, message) {
          errors.push(message);
          if (inputEl) {
            inputEl.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
            if (!firstInvalidElement) firstInvalidElement = inputEl;
          }
          const errEl = document.getElementById(errElId);
          if (errEl) {
            errEl.innerText = message;
            errEl.classList.remove('hidden');
          }
        }

        // 1. Student First Name (Required, min 2 chars)
        const firstName = document.getElementById('first_name');
        if (!firstName || !firstName.value.trim() || firstName.value.trim().length < 2) {
          markInvalid(firstName, 'first_name_client_err', 'Student First Name is required (at least 2 characters).');
        }

        // 2. Class (Required)
        const classId = document.getElementById('class_id');
        if (!classId || !classId.value.trim()) {
          markInvalid(classId, 'class_id_client_err', 'Please select the Standard / Class applying for.');
        }

        // 3. Date of Birth (Required, cannot be future, reasonable student age 2-22)
        const dob = document.getElementById('dob');
        if (!dob || !dob.value.trim()) {
          markInvalid(dob, 'dob_client_err', 'Student Date of Birth is required.');
        } else {
          const selectedDate = new Date(dob.value);
          const today = new Date();
          today.setHours(23, 59, 59, 999);
          if (selectedDate > today) {
            markInvalid(dob, 'dob_client_err', 'Date of Birth cannot be in the future.');
          }
        }

        // 4. Gender (Required)
        const gender = document.getElementById('gender');
        if (!gender || !gender.value.trim()) {
          markInvalid(gender, 'gender_client_err', 'Student Gender selection is required.');
        }

        // 5. Student Email (Optional, but if filled must be valid RFC format and unique)
        const email = document.getElementById('email');
        if (email && email.value.trim()) {
          if (!RFC_EMAIL_REGEX.test(email.value.trim())) {
            markInvalid(email, 'email_client_err', 'Please enter a valid student email address (e.g. student@example.com).');
          } else if (isEmailDuplicate) {
            markInvalid(email, 'email_client_err', emailDuplicateMsg || 'Student email is already registered to another student.');
          }
        }

        // 6. Student Aadhaar (Optional, but if filled must be exactly 12 digits and unique)
        const aadhaarNo = document.getElementById('aadhaar_no');
        if (aadhaarNo && aadhaarNo.value.trim()) {
          if (!/^\d{12}$/.test(aadhaarNo.value.trim())) {
            markInvalid(aadhaarNo, 'aadhaar_no_client_err', 'Student Aadhaar number must be exactly 12 numeric digits.');
          } else if (isAadhaarDuplicate) {
            markInvalid(aadhaarNo, 'aadhaar_no_client_err', aadhaarDuplicateMsg || 'Student Aadhaar number is already used for another admission.');
          }
        }

        // 7. EMIS / PEN Number (OPTIONAL: Never block if blank, but if filled must be unique)
        const emisNo = document.getElementById('emis_no');
        if (emisNo && emisNo.value.trim()) {
          if (emisNo.value.trim().length > 30) {
            markInvalid(emisNo, 'emis_no_client_err', 'EMIS / PEN Number cannot exceed 30 characters.');
          } else if (isEmisDuplicate) {
            markInvalid(emisNo, 'emis_no_client_err', emisDuplicateMsg || 'EMIS / PEN Number is already used for another admission.');
          }
        }

        // 8. Pincode (Optional, but if filled must be 6 digits starting with 1-9)
        const pincode = document.getElementById('pincode');
        if (pincode && pincode.value.trim()) {
          if (!/^[1-9]\d{5}$/.test(pincode.value.trim())) {
            markInvalid(pincode, 'pincode_client_err', 'Pincode must be a valid 6-digit postal code (e.g. 600001).');
          }
        }

        // 9. Father / Primary Parent Name (Required, min 2 chars)
        const parentName = document.getElementById('parent_name');
        if (!parentName || !parentName.value.trim() || parentName.value.trim().length < 2) {
          markInvalid(parentName, 'parent_name_client_err', 'Father / Primary Parent Name is required.');
        }

        // 10. Father Mobile (REQUIRED: exactly 10 digits starting with 6-9)
        const parentMobile = document.getElementById('parent_mobile');
        if (!parentMobile || !parentMobile.value.trim()) {
          markInvalid(parentMobile, 'parent_mobile_client_err', 'Father Mobile Number is required (10 digits).');
        } else {
          formatPhoneInput(parentMobile);
          const cleanMobile = parentMobile.value.trim();
          if (!/^[6-9]\d{9}$/.test(cleanMobile)) {
            markInvalid(parentMobile, 'parent_mobile_client_err', 'Father Mobile number must be a valid 10-digit Indian mobile starting with 6, 7, 8, or 9.');
          }
        }

        // 11. Father Email (Optional, but if filled must be valid RFC format)
        const parentEmail = document.getElementById('parent_email');
        if (parentEmail && parentEmail.value.trim()) {
          if (!RFC_EMAIL_REGEX.test(parentEmail.value.trim())) {
            markInvalid(parentEmail, 'parent_email_client_err', 'Please enter a valid Father email address (e.g. father@example.com).');
          }
        }

        // 12. Father Aadhaar (Optional, but if filled must be 12 digits)
        const fatherAadhaar = document.getElementById('father_aadhaar');
        if (fatherAadhaar && fatherAadhaar.value.trim()) {
          if (!/^\d{12}$/.test(fatherAadhaar.value.trim())) {
            markInvalid(fatherAadhaar, 'father_aadhaar_client_err', 'Father Aadhaar number must be exactly 12 numeric digits.');
          }
        }

        // 13. Mother Mobile (Optional, but if filled must be 10 digits starting with 6-9)
        const motherMobile = document.getElementById('mother_mobile');
        if (motherMobile && motherMobile.value.trim()) {
          formatPhoneInput(motherMobile);
          const cleanMother = motherMobile.value.trim();
          if (!/^[6-9]\d{9}$/.test(cleanMother)) {
            markInvalid(motherMobile, 'mother_mobile_client_err', 'Mother Mobile number must be a valid 10-digit mobile starting with 6, 7, 8, or 9.');
          }
        }

        // 14. Mother Email (Optional, but if filled must be valid RFC format)
        const motherEmail = document.getElementById('mother_email');
        if (motherEmail && motherEmail.value.trim()) {
          if (!RFC_EMAIL_REGEX.test(motherEmail.value.trim())) {
            markInvalid(motherEmail, 'mother_email_client_err', 'Please enter a valid Mother email address (e.g. mother@example.com).');
          }
        }

        // 15. Guardian Mobile (Optional, but if filled must be 10 digits starting with 6-9)
        const guardianMobile = document.getElementById('guardian_mobile');
        if (guardianMobile && guardianMobile.value.trim()) {
          formatPhoneInput(guardianMobile);
          const cleanGuardian = guardianMobile.value.trim();
          if (!/^[6-9]\d{9}$/.test(cleanGuardian)) {
            markInvalid(guardianMobile, 'guardian_mobile_client_err', 'Guardian Mobile number must be a valid 10-digit mobile starting with 6, 7, 8, or 9.');
          }
        }

        // 16. Sibling Roll No (If sibling checkbox is checked)
        const hasSiblingCheck = document.getElementById('has_sibling');
        const siblingRoll = document.getElementById('sibling_roll_no');
        if (hasSiblingCheck && hasSiblingCheck.checked) {
          if (!siblingRoll || !siblingRoll.value.trim()) {
            markInvalid(siblingRoll, 'sibling_roll_no_client_err', 'Please enter enrolled Sibling Roll Number (or uncheck sibling option).');
          }
        }

        // 17. Amount Collected (Required and must be non-negative)
        const amountCollected = document.getElementById('amount_collected');
        if (!amountCollected || amountCollected.value === '' || Number(amountCollected.value) < 0) {
          markInvalid(amountCollected, 'amount_collected_client_err', 'Amount Collected Today is required (enter 0 if paying later).');
        }

        // 18. Previous Percentage (Optional, but if filled must be between 0 and 100)
        const prevPct = document.getElementById('previous_percentage');
        if (prevPct && prevPct.value.trim() !== '') {
          const val = Number(prevPct.value);
          if (isNaN(val) || val < 0 || val > 100) {
            markInvalid(prevPct, 'previous_percentage_client_err', 'Previous Percentage / CGPA must be between 0 and 100.');
          }
        }

        // If any error exists:
        if (errors.length > 0) {
          e.preventDefault();

          // Render client error banner at top of form
          const clientAlert = document.getElementById('clientValidationAlert');
          const clientList = document.getElementById('clientValidationList');
          const clientTitle = document.getElementById('clientValidationTitle');
          if (clientAlert && clientList) {
            clientTitle.innerText = `Please correct the following (${errors.length}) issues before submitting:`;
            clientList.innerHTML = errors.map(msg => `<li>${msg}</li>`).join('');
            clientAlert.classList.remove('hidden');
          }

          // Smoothly scroll to the first invalid field
          if (firstInvalidElement) {
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
              firstInvalidElement.focus();
            }, 300);
          } else if (clientAlert) {
            clientAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }

          // Preserve draft buffer immediately so no data is lost on validation failure
          saveAdmissionDraft();
        } else {
          // Client validation passed!
          // Double-submission lock:
          const alpineRoot = document.querySelector('[x-data]');
          if (alpineRoot && window.Alpine) {
            const alpineData = Alpine.$data(alpineRoot);
            if (alpineData) alpineData.isSubmitting = true;
          }
          // Save draft right before submitting as buffer against connection drops
          saveAdmissionDraft();
        }
      });
    }

    // ── Resilient Auto-Draft Storage Engine ──────────────────────────
    const DRAFT_KEY = 'dasa_admission_draft_v2';
    let draftSaveTimer = null;

    function saveAdmissionDraft() {
      const formEl = document.getElementById('admissionCreateForm');
      if (!formEl) return;

      const draft = {
        savedAt: new Date().toISOString(),
        fields: {},
        alpine: {}
      };

      // Collect standard form fields (excluding files and tokens)
      const elements = formEl.elements;
      for (let i = 0; i < elements.length; i++) {
        const el = elements[i];
        if (!el.name || el.type === 'file' || el.name === '_token') continue;
        if (el.type === 'checkbox') {
          draft.fields[el.name] = el.checked;
        } else if (el.type === 'radio') {
          if (el.checked) draft.fields[el.name] = el.value;
        } else {
          draft.fields[el.name] = el.value;
        }
      }

      // Collect reactive Alpine state
      const alpineRoot = document.querySelector('[x-data]');
      if (alpineRoot && window.Alpine) {
        const ad = Alpine.$data(alpineRoot);
        if (ad) {
          draft.alpine = {
            selectedClassId: ad.selectedClassId || '',
            selectedSectionId: ad.selectedSectionId || '',
            userAmountCollected: ad.userAmountCollected || '',
            concessionType: ad.concessionType || 'none',
            concessionAmountInput: ad.concessionAmountInput || '',
            concessionRemarks: ad.concessionRemarks || '',
            aspRequired: ad.aspRequired || false,
            aspTerm: ad.aspTerm || '',
            transportRequired: ad.transportRequired || false,
            selectedRouteId: ad.selectedRouteId || '',
            selectedStopId: ad.selectedStopId || '',
            isPaymentVerified: ad.isPaymentVerified || false,
            verifiedAt: ad.verifiedAt || '',
            verifiedBy: ad.verifiedBy || '',
            verificationNotes: ad.verificationNotes || '',
            verificationHistory: ad.verificationHistory || []
          };
        }
      }

      try {
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
      } catch (err) {
        console.warn('Draft save error:', err);
      }
    }

    function saveAdmissionDraftDebounced() {
      clearTimeout(draftSaveTimer);
      draftSaveTimer = setTimeout(saveAdmissionDraft, 500);
    }

    // Auto-save draft on every user keystroke / selection change
    if (form) {
      form.addEventListener('input', saveAdmissionDraftDebounced);
      form.addEventListener('change', saveAdmissionDraftDebounced);
    }
    window.addEventListener('beforeunload', saveAdmissionDraft);

    // Auto-restore draft on page load
    const hasServerErrors = {{ $errors->any() ? 'true' : 'false' }};
    if (hasServerErrors) {
      // Server returned with errors — Laravel old() has already filled form fields.
      // Sync latest data into draft storage.
      saveAdmissionDraft();
    } else {
      // Fresh load or refresh — check if local draft exists
      try {
        const raw = localStorage.getItem(DRAFT_KEY);
        if (raw) {
          const draft = JSON.parse(raw);
          const firstNameInput = document.getElementById('first_name');
          // If first_name or mobile was entered in draft and current first_name is empty, restore!
          if (draft && draft.fields && (draft.fields['first_name'] || draft.fields['parent_mobile'] || draft.fields['class_id'])) {
            if (firstNameInput && !firstNameInput.value.trim()) {
              // Populate form fields
              for (const [name, val] of Object.entries(draft.fields)) {
                const el = form.elements[name];
                if (!el) continue;
                if (el.type === 'checkbox') {
                  el.checked = Boolean(val);
                } else if (el.type === 'radio') {
                  if (el.value === val) el.checked = true;
                } else {
                  el.value = val;
                }
              }

              // Restore Alpine reactive state
              const alpineRoot = document.querySelector('[x-data]');
              if (alpineRoot && window.Alpine && draft.alpine) {
                const ad = Alpine.$data(alpineRoot);
                if (ad) {
                  if (draft.alpine.selectedClassId) ad.selectedClassId = draft.alpine.selectedClassId;
                  if (draft.alpine.selectedSectionId) ad.selectedSectionId = draft.alpine.selectedSectionId;
                  if (draft.alpine.userAmountCollected) ad.userAmountCollected = draft.alpine.userAmountCollected;
                  if (draft.alpine.concessionType) ad.concessionType = draft.alpine.concessionType;
                  if (draft.alpine.concessionAmountInput) ad.concessionAmountInput = draft.alpine.concessionAmountInput;
                  if (draft.alpine.concessionRemarks) ad.concessionRemarks = draft.alpine.concessionRemarks;
                  if (draft.alpine.aspRequired !== undefined) ad.aspRequired = draft.alpine.aspRequired;
                  if (draft.alpine.transportRequired !== undefined) ad.transportRequired = draft.alpine.transportRequired;
                  if (draft.alpine.isPaymentVerified !== undefined) ad.isPaymentVerified = draft.alpine.isPaymentVerified;
                  if (draft.alpine.verifiedAt) ad.verifiedAt = draft.alpine.verifiedAt;
                  if (draft.alpine.verifiedBy) ad.verifiedBy = draft.alpine.verifiedBy;
                  if (draft.alpine.verificationHistory) ad.verificationHistory = draft.alpine.verificationHistory;
                }
              }

              // Display recovery banner
              const banner = document.getElementById('draftRecoveryBanner');
              const timeText = document.getElementById('draftTimestampText');
              if (banner && timeText && draft.savedAt) {
                const savedDate = new Date(draft.savedAt);
                timeText.innerText = `Previously entered data from ${savedDate.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })} (${savedDate.toLocaleDateString('en-IN')}) was restored. You can continue without retyping.`;
                banner.classList.remove('hidden');
              }
            }
          }
        }
      } catch (e) {
        console.warn('Draft restoration error:', e);
      }
    }
  });

  // Discard draft and reset form
  function discardDraftAndReset() {
    if (confirm('Are you sure you want to discard all unsaved admission data and start fresh?')) {
      try {
        localStorage.removeItem('dasa_admission_draft_v2');
      } catch (e) {}
      window.location.href = "{{ route('admissions.create') }}";
    }
  }
</script>
@endsection
