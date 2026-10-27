<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Profile — {{ $student->full_name }} ({{ $student->admission_no }})</title>
  <style>
    @page { size: A4 portrait; margin: 12mm 15mm; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #0f172a; font-size: 10pt; line-height: 1.4; margin: 0; padding: 15px; }
    .header { text-align: center; border-bottom: 2px solid #8C2826; padding-bottom: 10px; margin-bottom: 15px; }
    .school-title { font-size: 18pt; font-weight: 900; color: #8C2826; text-transform: uppercase; margin: 0; }
    .school-sub { font-size: 9pt; color: #64748b; margin-top: 3px; }
    .record-title { font-size: 12pt; font-weight: bold; color: #0f172a; text-transform: uppercase; margin-top: 6px; letter-spacing: 0.5px; }

    .hero-box { display: flex; align-items: flex-start; justify-content: space-between; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 15px; background: #f8fafc; }
    .hero-details { flex: 1; }
    .hero-photo { width: 90px; height: 110px; border: 1px solid #94a3b8; border-radius: 6px; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14pt; color: #64748b; }
    .hero-photo img { width: 100%; height: 100%; object-fit: cover; }

    .sec-title { font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #8C2826; border-bottom: 1.5px solid #8C2826; padding-bottom: 3px; margin: 12px 0 6px 0; letter-spacing: 0.5px; }
    .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 16px; margin-bottom: 8px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 16px; margin-bottom: 8px; }
    .item { display: flex; font-size: 9pt; }
    .item-label { width: 140px; color: #64748b; font-weight: 600; shrink-0; }
    .item-value { font-weight: bold; color: #0f172a; flex: 1; }

    table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 8.5pt; }
    th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 8px; text-align: left; font-weight: bold; text-transform: uppercase; }
    td { border: 1px solid #cbd5e1; padding: 5px 8px; }

    .signatures { margin-top: 40px; display: flex; justify-content: space-between; font-weight: bold; font-size: 9pt; }
    .sig-line { border-top: 1px solid #475569; width: 160px; text-align: center; padding-top: 5px; }

    @media print {
      body { padding: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

  <div class="no-print" style="margin-bottom: 15px; text-align: right;">
    <button onclick="window.print()" style="background-color: #8C2826; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer;">
      Print Official Profile
    </button>
  </div>

  <div class="header">
    <h1 class="school-title">DASA EduERP &bull; Student Cumulative Record</h1>
    <div class="school-sub">Affiliated to State Board / CBSE &bull; Comprehensive Student Dossier</div>
    <div class="record-title">Official Student Profile Dossier</div>
  </div>

  {{-- Hero Identity Card --}}
  <div class="hero-box">
    <div class="hero-details">
      <h2 style="margin: 0; font-size: 14pt; color: #0f172a;">{{ $student->full_name }}</h2>
      <div style="margin-top: 4px; font-size: 9.5pt; color: #8C2826; font-weight: bold;">
        Admission No: <span style="font-family: monospace;">{{ $student->admission_no }}</span>
        &bull; Roll No: <span style="font-family: monospace;">{{ $student->currentEnrollment?->roll_number ?: 'Not Assigned' }}</span>
      </div>
      <div style="margin-top: 4px; font-size: 9pt; color: #475569;">
        Standard: <strong>Class {{ $student->currentEnrollment?->class?->name ?? '—' }}</strong>
        &bull; Section: <strong>Section {{ $student->currentEnrollment?->section?->name ?? 'None' }}</strong>
        &bull; House: <strong>{{ $student->currentEnrollment?->houseGroup?->name ?? ($student->currentEnrollment?->house ?: 'Unallotted') }}</strong>
      </div>
      <div style="margin-top: 4px; font-size: 9pt; color: #475569;">
        Academic Year: <strong>{{ $student->currentEnrollment?->academicYear?->name ?? $academicYear?->name }}</strong>
        &bull; Status: <strong style="text-transform: uppercase;">{{ $student->status }}</strong>
      </div>
    </div>
    <div class="hero-photo">
      @if($student->photo)
        <img src="{{ asset('storage/'.$student->photo) }}" alt="Student Photo">
      @else
        <span>PHOTO</span>
      @endif
    </div>
  </div>

  {{-- 1. Personal Details --}}
  <div class="sec-title">1. Personal Information</div>
  <div class="grid">
    <div class="item"><span class="item-label">Date of Birth:</span><span class="item-value">{{ $student->dob ? $student->dob->format('d F Y') : '—' }}</span></div>
    <div class="item"><span class="item-label">Gender:</span><span class="item-value">{{ ucfirst($student->gender) }}</span></div>
    <div class="item"><span class="item-label">Blood Group:</span><span class="item-value">{{ $student->blood_group ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Nationality:</span><span class="item-value">{{ $student->nationality ?: 'Indian' }}</span></div>
    <div class="item"><span class="item-label">Mother Tongue:</span><span class="item-value">{{ $student->mother_tongue ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Religion / Caste:</span><span class="item-value">{{ $student->religion ?: '—' }} / {{ $student->caste ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Aadhaar Number:</span><span class="item-value" style="font-family: monospace;">{{ $student->aadhaar_no ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Student Contact:</span><span class="item-value">{{ $student->mobile ?: '—' }}</span></div>
  </div>
  <div class="item" style="margin-top: 2px;">
    <span class="item-label" style="width: 140px;">Residential Address:</span>
    <span class="item-value">{{ $student->residential_address ?: '—' }} (PIN: {{ $student->pincode ?: '—' }})</span>
  </div>

  {{-- 2. Parent & Guardian Information --}}
  <div class="sec-title">2. Parent &amp; Guardian Information</div>
  <div class="grid">
    <div class="item"><span class="item-label">Father's Name:</span><span class="item-value">{{ $student->father_name ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Father's Contact:</span><span class="item-value" style="font-family: monospace;">{{ $student->father_mobile ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Father's Occupation:</span><span class="item-value">{{ $student->father_occupation ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Father's Email:</span><span class="item-value">{{ $student->father_email ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Mother's Name:</span><span class="item-value">{{ $student->mother_name ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Mother's Contact:</span><span class="item-value" style="font-family: monospace;">{{ $student->mother_mobile ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Mother's Occupation:</span><span class="item-value">{{ $student->mother_occupation ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Emergency Contact:</span><span class="item-value" style="font-family: monospace;">{{ $student->emergency_contact_name ? $student->emergency_contact_name . ' (' . $student->emergency_contact_mobile . ')' : '—' }}</span></div>
  </div>

  {{-- 3. Academic & Admission Details --}}
  <div class="sec-title">3. Academic &amp; Admission Profile</div>
  <div class="grid">
    <div class="item"><span class="item-label">Admission Date:</span><span class="item-value">{{ $student->admission_date ? $student->admission_date->format('d/m/Y') : '—' }}</span></div>
    <div class="item"><span class="item-label">Student Type:</span><span class="item-value">{{ ucwords(str_replace('_', ' ', $student->student_type ?: 'Day Scholar')) }}</span></div>
    <div class="item"><span class="item-label">EMIS / PEN Number:</span><span class="item-value" style="font-family: monospace;">{{ $student->emis_no ?: '—' }}</span></div>
    <div class="item"><span class="item-label">Previous School:</span><span class="item-value">{{ $student->previous_school_name ?: '—' }} ({{ $student->previous_school_board ?: '—' }})</span></div>
    <div class="item"><span class="item-label">Transport Route:</span><span class="item-value">{{ $student->transportRoute?->route_name ?: 'Not Enrolled (Own Transport)' }}</span></div>
    <div class="item"><span class="item-label">Designated Stop:</span><span class="item-value">{{ $student->transportStop?->stop_name ?: '—' }}</span></div>
  </div>

  {{-- 4. Submitted Documents Checklist --}}
  <div class="sec-title">4. Document Verification Checklist</div>
  <table>
    <thead>
      <tr>
        <th style="width: 40px;">S.No</th>
        <th>Document Category</th>
        <th style="width: 140px;">Verification Status</th>
        <th style="width: 140px;">Upload Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse($documents as $idx => $doc)
        <tr>
          <td style="text-align: center;">{{ $idx + 1 }}</td>
          <td><strong>{{ ucwords(str_replace('_', ' ', $doc->document_type)) }}</strong></td>
          <td>
            <span style="font-weight: bold; color: {{ $doc->status === 'verified' ? '#059669' : '#d97706' }};">
              {{ strtoupper($doc->status ?: 'Pending') }}
            </span>
          </td>
          <td>{{ $doc->created_at ? $doc->created_at->format('d/m/Y') : '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" style="text-align: center; color: #94a3b8; padding: 10px;">No document records uploaded in file vault.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="signatures">
    <div class="sig-line">Parent / Guardian Signature</div>
    <div class="sig-line">Class Teacher Signature</div>
    <div class="sig-line">Principal / Authorized Seal</div>
  </div>

</body>
</html>
