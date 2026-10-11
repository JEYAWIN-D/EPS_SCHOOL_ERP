<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Class Timetable — {{ $class->display_name }} (Section {{ $selectedSection->name ?? 'A' }})</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 9px;
    color: #1e293b;
    background: #ffffff;
    padding: 16px 24px;
  }
  .header-table {
    width: 100%;
    border-bottom: 2.5px solid #1e3a8a;
    padding-bottom: 10px;
    margin-bottom: 12px;
  }
  .school-title {
    font-size: 18px;
    font-weight: 800;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .school-sub {
    font-size: 9px;
    color: #64748b;
    margin-top: 2px;
  }
  .doc-badge {
    text-align: right;
  }
  .doc-badge .badge-title {
    font-size: 13px;
    font-weight: 800;
    color: #1e40af;
  }
  .doc-badge .badge-meta {
    font-size: 9px;
    color: #475569;
    margin-top: 2px;
  }
  .info-bar {
    width: 100%;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 12px;
    margin-bottom: 12px;
  }
  .info-bar td {
    font-size: 9px;
    color: #334155;
  }
  .info-bar strong {
    color: #0f172a;
  }
  .stream-box {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
    padding: 5px 10px;
    margin-bottom: 12px;
    font-size: 9.5px;
    color: #1e40af;
    font-weight: 600;
  }
  table.schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
  }
  table.schedule-table th {
    background: #1e3a8a;
    color: #ffffff;
    font-size: 8.5px;
    font-weight: 700;
    text-align: center;
    padding: 6px 4px;
    border: 1px solid #1e3a8a;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  table.schedule-table th.day-th {
    width: 75px;
    background: #0f172a;
  }
  table.schedule-table td {
    border: 1px solid #cbd5e1;
    padding: 6px 4px;
    vertical-align: top;
    font-size: 8.5px;
    height: 48px;
    background: #ffffff;
  }
  table.schedule-table tr:nth-child(even) td {
    background: #f8fafc;
  }
  table.schedule-table td.day-cell {
    background: #f1f5f9;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    color: #1e3a8a;
    border-right: 2px solid #94a3b8;
  }
  table.schedule-table td.saturday-cell {
    background: #ecfdf5;
    color: #065f46;
  }
  .subject-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 8.5px;
    line-height: 1.15;
  }
  .teacher-name {
    font-size: 7.5px;
    color: #64748b;
    margin-top: 2px;
  }
  .room-name {
    font-size: 7px;
    color: #94a3b8;
    margin-top: 1px;
  }
  .break-header {
    background: #fef3c7 !important;
    color: #92400e !important;
    font-size: 7.5px;
    padding: 3px;
    text-align: center;
  }
  .extracurricular-tag {
    display: inline-block;
    background: #d1fae5;
    color: #065f46;
    font-size: 7px;
    font-weight: 700;
    padding: 1px 4px;
    border-radius: 3px;
    margin-top: 2px;
  }
  .footer-table {
    width: 100%;
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px dashed #cbd5e1;
  }
  .sig-block {
    text-align: center;
    font-size: 8.5px;
    color: #475569;
  }
  .sig-line {
    width: 120px;
    border-bottom: 1px solid #64748b;
    margin: 0 auto 4px auto;
  }
</style>
</head>
<body>

{{-- Header --}}
<table class="header-table">
  <tr>
    <td style="width:65%;">
      <div class="school-title">{{ $school->school_name ?? config('app.name', 'DASA EDUERP') }}</div>
      <div class="school-sub">{{ $school->address ?? 'Main Campus, Knowledge Boulevard' }} | Phone: {{ $school->phone ?? '+91 98765 43210' }}</div>
    </td>
    <td class="doc-badge" style="width:35%;">
      <div class="badge-title">OFFICIAL CLASS TIMETABLE</div>
      <div class="badge-meta">Academic Session: <strong>{{ $currentYear->name ?? '2025-2026' }}</strong></div>
      <div class="badge-meta">School Timing: <strong>09:15 AM — 04:30 PM</strong></div>
    </td>
  </tr>
</table>

{{-- Class & Section Meta Bar --}}
<table class="info-bar">
  <tr>
    <td style="width:25%;">Class: <strong>{{ $class->display_name }}</strong></td>
    <td style="width:25%;">Section: <strong>Section {{ $selectedSection->name ?? 'A' }}</strong></td>
    <td style="width:30%;">Class Teacher: <strong>{{ $selectedSection->classTeacher?->name ?? 'Senior Faculty' }}</strong></td>
    <td style="width:20%;text-align:right;">Room: <strong>{{ $selectedSection ? ($class->numeric_value > 0 ? ('Room ' . $class->numeric_value . '-' . $selectedSection->name) : ('KG-' . $selectedSection->name)) : '—' }}</strong></td>
  </tr>
</table>

@if($streamName)
<div class="stream-box">
  Curriculum Stream: {{ $streamName }}
</div>
@endif

{{-- Timetable Schedule Matrix (Periods 1 to 8, Mon to Sat) --}}
@php
  $days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday (Extracurricular)'
  ];

  $periodTimings = [
    1 => ['09:10 AM', '09:50 AM'],
    2 => ['09:50 AM', '10:30 AM'],
    3 => ['10:40 AM', '11:20 AM'],
    4 => ['11:20 AM', '12:00 PM'],
    5 => ['12:00 PM', '12:40 PM'],
    6 => ['01:20 PM', '02:00 PM'],
    7 => ['02:10 PM', '02:50 PM'],
    8 => ['02:50 PM', '03:00 PM'],
  ];
@endphp

<table class="schedule-table">
  <thead>
    <tr>
      <th class="day-th">Day / Timing</th>
      @for($p = 1; $p <= 8; $p++)
        <th>
          {{ $p === 8 ? 'Diary' : 'P' . $p }}<br>
          <span style="font-size:7px;font-weight:400;opacity:0.85;">{{ $periodTimings[$p][0] }}–{{ $periodTimings[$p][1] }}</span>
        </th>
      @endfor
    </tr>
  </thead>
  <tbody>
    @foreach($days as $dayNum => $dayName)
      @php
        $dayEntries = $timetable[$dayNum] ?? collect();
        $isSaturday = ($dayNum === 6);
      @endphp
      <tr>
        <td class="day-cell {{ $isSaturday ? 'saturday-cell' : '' }}">
          <strong>{{ $isSaturday ? 'Saturday' : $dayName }}</strong>
          @if($isSaturday)
            <div style="font-size:6.5px;color:#059669;margin-top:2px;">Extracurricular</div>
          @endif
        </td>

        @for($p = 1; $p <= 8; $p++)
          @php
            $entry = $dayEntries->firstWhere('period_number', $p)
                  ?? $dayEntries->values()->get($p - 1);
          @endphp
          <td>
            @if($entry)
              <div class="subject-name">{{ $entry->subject?->name ?? 'Academic Class' }}</div>
              <div class="teacher-name">{{ $entry->teacher ? ($entry->teacher->first_name . ' ' . $entry->teacher->last_name) : 'Subject Teacher' }}</div>
              <div class="room-name">{{ $entry->room ?? '' }}</div>
              @if($isSaturday)
                <span class="extracurricular-tag">Club Activity</span>
              @endif
            @else
              <div style="color:#cbd5e1;text-align:center;padding-top:10px;">—</div>
            @endif
          </td>
        @endfor
      </tr>
    @endforeach
  </tbody>
</table>

{{-- Timing & Interval Legend --}}
<table style="width:100%;margin-top:10px;font-size:8px;color:#64748b;">
  <tr>
    <td><strong>Daily Bell Schedule:</strong> Period I: 09:10–09:50 | Period II: 09:50–10:30 | Interval: 10:30–10:40 | Period III: 10:40–11:20 | Period IV: 11:20–12:00 | Period V: 12:00–12:40 | Lunch: 12:40–01:20 | Period VI: 01:20–02:00 | Interval: 02:00–02:10 | Period VII: 02:10–02:50 | Diary: 02:50–03:00 PM</td>
    <td style="text-align:right;">Generated on: {{ now()->format('d M Y, h:i A') }}</td>
  </tr>
</table>

{{-- Signatures --}}
<table class="footer-table">
  <tr>
    <td class="sig-block" style="width:25%;">
      <div class="sig-line"></div>
      <div>Class Teacher Signature</div>
    </td>
    <td class="sig-block" style="width:25%;">
      <div class="sig-line"></div>
      <div>Academic Coordinator</div>
    </td>
    <td class="sig-block" style="width:25%;">
      <div class="sig-line"></div>
      <div>Timetable Committee</div>
    </td>
    <td class="sig-block" style="width:25%;">
      <div class="sig-line"></div>
      <div>Principal / Headmaster</div>
    </td>
  </tr>
</table>

</body>
</html>
