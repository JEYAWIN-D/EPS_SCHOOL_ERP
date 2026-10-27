<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Roll Number Roster — Class {{ $class?->name }} {{ $section ? 'Section ' . $section->name : '' }}</title>
  <style>
    @page { size: A4 portrait; margin: 15mm; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11pt; line-height: 1.4; margin: 0; padding: 20px; }
    .header { text-align: center; border-bottom: 2px solid #8C2826; padding-bottom: 12px; margin-bottom: 20px; }
    .school-name { font-size: 18pt; font-weight: 900; color: #8C2826; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
    .school-sub { font-size: 10pt; color: #64748b; margin-top: 4px; }
    .sheet-title { font-size: 13pt; font-weight: bold; color: #0f172a; margin-top: 8px; text-transform: uppercase; }
    .meta-bar { display: flex; justify-content: space-between; font-size: 10pt; font-weight: 600; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9.5pt; }
    th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 8.5pt; }
    td { border: 1px solid #cbd5e1; padding: 6px 10px; }
    tr:nth-child(even) { background-color: #f8fafc; }
    .text-center { text-align: center; }
    .font-mono { font-family: monospace; }
    .font-bold { font-weight: bold; }
    .signatures { margin-top: 50px; display: flex; justify-content: space-between; font-weight: bold; font-size: 10pt; }
    .sig-line { border-top: 1px solid #475569; width: 180px; text-align: center; padding-top: 6px; }
    @media print {
      body { padding: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

  <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: flex-end; gap: 10px;">
    <button onclick="window.print()" style="background-color: #8C2826; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">
      Print Roster Sheet
    </button>
  </div>

  <div class="header">
    <h1 class="school-name">DASA EduERP &bull; School Management</h1>
    <div class="school-sub">Official Student Roll Number Register &amp; Attendance Roster</div>
    <div class="sheet-title">
      Class {{ $class?->name ?? 'All Classes' }}
      @if($section) &mdash; Section {{ $section->name }} @endif
      ({{ $academicYear?->name ?? 'Current Academic Session' }})
    </div>
  </div>

  <div class="meta-bar">
    <div>Standard: <strong>Class {{ $class?->name ?? 'All' }}</strong></div>
    @if($section) <div>Section: <strong>Section {{ $section->name }}</strong></div> @endif
    <div>Class Teacher: <strong>{{ $section?->classTeacher?->name ?? 'Not Assigned' }}</strong></div>
    <div>Total Strength: <strong>{{ $enrollments->count() }} Students</strong></div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="text-center" style="width: 45px;">S.No</th>
        <th class="text-center" style="width: 80px;">Roll No.</th>
        <th class="text-center" style="width: 100px;">Admission No.</th>
        <th>Student Name</th>
        <th class="text-center" style="width: 70px;">Gender</th>
        <th class="text-center" style="width: 90px;">Date of Birth</th>
        <th>Parent / Guardian</th>
        <th class="text-center" style="width: 110px;">Parent Mobile</th>
      </tr>
    </thead>
    <tbody>
      @forelse($enrollments as $idx => $enr)
        @php $st = $enr->student; @endphp
        <tr>
          <td class="text-center font-mono">{{ $idx + 1 }}</td>
          <td class="text-center font-mono font-bold">{{ $enr->roll_number ?: '—' }}</td>
          <td class="text-center font-mono font-bold">{{ $st?->admission_no }}</td>
          <td class="font-bold">{{ $st?->full_name }}</td>
          <td class="text-center">{{ ucfirst($st?->gender ?: '') }}</td>
          <td class="text-center">{{ $st?->dob ? $st->dob->format('d/m/Y') : '—' }}</td>
          <td>{{ $st?->father_name ?: ($st?->mother_name ?: '—') }}</td>
          <td class="text-center font-mono">{{ $st?->father_mobile ?: ($st?->mobile ?: '—') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center" style="padding: 20px;">No enrolled students found for the selected criteria.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="signatures">
    <div class="sig-line">Class Teacher Signature</div>
    <div class="sig-line">Exam Coordinator Signature</div>
    <div class="sig-line">Principal / Headmaster</div>
  </div>

</body>
</html>
