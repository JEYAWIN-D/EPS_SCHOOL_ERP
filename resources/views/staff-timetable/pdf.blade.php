<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Faculty Timetable — {{ $teacher->first_name }} {{ $teacher->last_name }} ({{ $teacher->employee_code }})</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 12mm 10mm 12mm 10mm;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      font-size: 11px;
      line-height: 1.3;
      color: #1e293b;
      margin: 0;
      padding: 0;
      background: #ffffff;
    }
    .header-table {
      width: 100%;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 8px;
      margin-bottom: 12px;
    }
    .school-title {
      font-size: 18px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
      text-transform: uppercase;
      margin: 0;
    }
    .school-sub {
      font-size: 11px;
      color: #475569;
      font-weight: 600;
      margin: 2px 0 0 0;
    }
    .doc-title {
      font-size: 14px;
      font-weight: 800;
      color: #4338ca;
      text-align: right;
      margin: 0;
      text-transform: uppercase;
    }
    .doc-sub {
      font-size: 10px;
      color: #64748b;
      text-align: right;
      margin: 2px 0 0 0;
    }
    .info-card {
      width: 100%;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 8px 12px;
      margin-bottom: 12px;
      box-sizing: border-box;
    }
    .info-grid {
      width: 100%;
      border-collapse: collapse;
    }
    .info-grid td {
      padding: 2px 8px;
      font-size: 11px;
    }
    .info-label {
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      font-size: 9.5px;
    }
    .info-value {
      font-weight: 700;
      color: #0f172a;
    }
    .timetable-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    .timetable-table th {
      background: #0f172a;
      color: #ffffff;
      padding: 6px 4px;
      font-size: 9.5px;
      font-weight: 800;
      text-align: center;
      border: 1px solid #334155;
    }
    .timetable-table th.day-col {
      width: 80px;
      text-align: left;
      padding-left: 8px;
    }
    .timetable-table th .time-sub {
      font-weight: 400;
      font-size: 8px;
      color: #cbd5e1;
      margin-top: 2px;
    }
    .timetable-table td {
      border: 1px solid #cbd5e1;
      padding: 4px;
      vertical-align: top;
      font-size: 9.5px;
    }
    .timetable-table td.day-cell {
      background: #f1f5f9;
      font-weight: 800;
      color: #0f172a;
      text-transform: uppercase;
      font-size: 10px;
      padding-left: 8px;
    }
    .class-box {
      background: #eef2ff;
      border-left: 3px solid #4f46e5;
      padding: 3px 4px;
      border-radius: 3px;
    }
    .sub-name {
      font-weight: 800;
      color: #1e1b4b;
      line-height: 1.1;
      margin-bottom: 2px;
    }
    .class-name {
      font-weight: 700;
      color: #4338ca;
      font-size: 8.5px;
    }
    .room-name {
      font-size: 8px;
      color: #64748b;
    }
    .free-box {
      background: #f0fdf4;
      border: 1px dashed #86efac;
      padding: 4px;
      border-radius: 3px;
      text-align: center;
      color: #15803d;
      font-weight: 700;
      font-size: 8.5px;
    }
    .extra-box {
      background: #faf5ff;
      border-left: 3px solid #9333ea;
      padding: 3px 4px;
      border-radius: 3px;
    }
    .break-cell {
      background: #f8fafc;
      text-align: center;
      color: #94a3b8;
      font-weight: 800;
      font-size: 8px;
      width: 14px;
      padding: 2px;
    }
    .summary-bar {
      width: 100%;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 8px 12px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      box-sizing: border-box;
    }
    .signatures-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }
    .signatures-table td {
      width: 33.33%;
      text-align: center;
      vertical-align: bottom;
      padding: 0 20px;
    }
    .sign-line {
      border-top: 1px solid #475569;
      padding-top: 6px;
      font-weight: 700;
      font-size: 10.5px;
      color: #1e293b;
    }
    .sign-sub {
      font-size: 9px;
      color: #64748b;
      margin-top: 2px;
    }
    .no-print-bar {
      background: #1e293b;
      color: white;
      padding: 10px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      border-radius: 8px;
    }
    @media print {
      .no-print-bar {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  {{-- Interactive Bar on screen --}}
  <div class="no-print-bar">
    <div>
      <strong>Faculty Timetable Print Preview</strong> &bull; Generated: {{ now()->format('d M Y, h:i A') }}
    </div>
    <div>
      <button onclick="window.print()" style="background:#4f46e5;color:white;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:12px;">
        🖨️ Print Now
      </button>
      <button onclick="window.close()" style="background:#475569;color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-weight:bold;font-size:12px;margin-left:8px;">
        Close Window
      </button>
    </div>
  </div>

  {{-- Institutional Letterhead --}}
  <table class="header-table">
    <tr>
      <td style="vertical-align: middle;">
        <h1 class="school-title">{{ config('app.name', 'DASA EPS SCHOOL') }}</h1>
        <p class="school-sub">Affiliated to CBSE, New Delhi &bull; Academic Schedule & Faculty Management</p>
      </td>
      <td style="vertical-align: middle; text-align: right;">
        <h2 class="doc-title">Individual Staff Timetable</h2>
        <p class="doc-sub">Week: {{ $weeklyMatrix['week_label'] ?? '' }} &bull; Academic Year 2025-2026</p>
      </td>
    </tr>
  </table>

  {{-- Faculty Information Card --}}
  <div class="info-card">
    <table class="info-grid">
      <tr>
        <td>
          <span class="info-label">Faculty Member:</span><br>
          <span class="info-value" style="font-size: 13px;">{{ $teacher->first_name }} {{ $teacher->last_name }}</span>
        </td>
        <td>
          <span class="info-label">Staff Code / ID:</span><br>
          <span class="info-value">{{ $teacher->employee_code }}</span>
        </td>
        <td>
          <span class="info-label">Department:</span><br>
          <span class="info-value">{{ $teacher->department?->name ?? 'Academics' }}</span>
        </td>
        <td>
          <span class="info-label">Designation:</span><br>
          <span class="info-value">{{ $teacher->designation?->name ?? 'Faculty' }}</span>
        </td>
        <td>
          <span class="info-label">Teaching Hours:</span><br>
          <span class="info-value">{{ $weeklyMatrix['summary']['weekly_teaching_hours'] ?? 0 }} hrs / week</span>
        </td>
      </tr>
    </table>
  </div>

  {{-- Timetable Matrix Grid --}}
  <table class="timetable-table">
    <thead>
      <tr>
        <th class="day-col">Day / Time</th>
        @foreach(App\Services\StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
          <th>
            {{ $slot['name'] }}
            <div class="time-sub">{{ $slot['start_time'] }}-{{ $slot['end_time'] }}</div>
          </th>
          @if($pNum == 2)
            <th class="break-cell" title="Morning Break">B<br>R<br>K</th>
          @elseif($pNum == 4)
            <th class="break-cell" title="Lunch Break">L<br>U<br>N</th>
          @elseif($pNum == 6)
            <th class="break-cell" title="Afternoon Break">B<br>R<br>K</th>
          @endif
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($weeklyMatrix['days'] ?? [] as $dayIdx => $dayData)
        <tr>
          <td class="day-cell">
            {{ $dayData['day_name'] }}
            <div style="font-size: 8px; font-weight: normal; color: #64748b;">
              {{ Carbon\Carbon::parse($dayData['date'])->format('d M') }}
            </div>
          </td>

          @if($dayData['is_off_day'] ?? false)
            <td colspan="11" style="text-align: center; color: #e11d48; font-weight: bold; background: #fff1f2;">
              {{ $dayData['off_reason'] }}
            </td>
          @else
            @foreach(App\Services\StaffTimetableService::getStandardPeriodsConfig() as $pNum => $slot)
              @php $cell = $dayData['periods'][$pNum] ?? null; @endphp
              <td>
                @if($cell && $cell['period_type'] === 'regular_class')
                  <div class="class-box">
                    <div class="sub-name">{{ $cell['subject_name'] }}</div>
                    <div class="class-name">Class {{ $cell['class_name'] }}-{{ $cell['section_name'] }}</div>
                    <div class="room-name">{{ $cell['room'] }}</div>
                  </div>
                @elseif($cell && $cell['period_type'] === 'extra_class')
                  <div class="extra-box">
                    <div class="sub-name" style="color:#7e22ce;">{{ $cell['subject_name'] }} [Extra]</div>
                    <div class="class-name" style="color:#6b21a8;">Class {{ $cell['class_name'] }}-{{ $cell['section_name'] }}</div>
                    <div class="room-name">{{ $cell['room'] }}</div>
                  </div>
                @elseif($cell && $cell['period_type'] === 'substitution')
                  <div class="extra-box" style="border-left-color: #d97706; background: #fffbeb;">
                    <div class="sub-name" style="color:#b45309;">{{ $cell['subject_name'] }} [Sub]</div>
                    <div class="class-name" style="color:#92400e;">Class {{ $cell['class_name'] }}-{{ $cell['section_name'] }}</div>
                  </div>
                @elseif($cell && in_array($cell['period_type'], ['exam_duty', 'meeting', 'remedial', 'practical_lab']))
                  <div class="extra-box" style="border-left-color: #ea580c; background: #fff7ed;">
                    <div class="sub-name" style="color:#c2410c;">{{ $cell['period_label'] }}</div>
                    <div class="room-name">{{ $cell['room'] }}</div>
                  </div>
                @else
                  <div class="free-box">
                    Free Period
                  </div>
                @endif
              </td>

              @if($pNum == 2 || $pNum == 4 || $pNum == 6)
                <td class="break-cell">&bull;</td>
              @endif
            @endforeach
          @endif
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Workload Summary Bar --}}
  <table style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; background: #f8fafc; padding: 6px 12px; margin-bottom: 24px;">
    <tr>
      <td style="font-weight: 700; color: #334155;">
        Total Teaching Periods: <span style="color: #4338ca;">{{ $weeklyMatrix['summary']['weekly_teaching_periods'] ?? 0 }} periods</span>
      </td>
      <td style="font-weight: 700; color: #334155;">
        Weekly Teaching Hours: <span style="color: #4338ca;">{{ $weeklyMatrix['summary']['weekly_teaching_hours'] ?? 0 }} hrs</span>
      </td>
      <td style="font-weight: 700; color: #334155;">
        Free Period Slots: <span style="color: #15803d;">{{ $weeklyMatrix['summary']['weekly_free_periods'] ?? 0 }} slots</span>
      </td>
      <td style="font-weight: 700; color: #334155;">
        Extra Classes / Subs: <span style="color: #9333ea;">{{ ($weeklyMatrix['summary']['weekly_extra_classes'] ?? 0) + ($weeklyMatrix['summary']['weekly_substitutions'] ?? 0) }}</span>
      </td>
    </tr>
  </table>

  {{-- Signatures Table --}}
  <table class="signatures-table">
    <tr>
      <td>
        <div class="sign-line">Faculty Member Signature</div>
        <div class="sign-sub">{{ $teacher->first_name }} {{ $teacher->last_name }}</div>
      </td>
      <td>
        <div class="sign-line">Academic Coordinator</div>
        <div class="sign-sub">Verified & Approved</div>
      </td>
      <td>
        <div class="sign-line">Principal / Head of Institution</div>
        <div class="sign-sub">Institutional Stamp & Seal</div>
      </td>
    </tr>
  </table>

</body>
</html>
