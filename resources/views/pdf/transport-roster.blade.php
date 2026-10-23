<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Transport Roster Manifest</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 8.5px;
    color: #1e293b;
    background: #ffffff;
    line-height: 1.35;
    padding: 16px 20px;
  }

  .header {
    text-align: center;
    border-bottom: 2px solid #1e3a8a;
    padding-bottom: 10px;
    margin-bottom: 12px;
  }
  .school-name {
    font-size: 15px;
    font-weight: 800;
    color: #1e3a8a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .school-sub {
    font-size: 8.5px;
    color: #475569;
    margin-top: 2px;
  }
  .doc-title {
    font-size: 11px;
    font-weight: bold;
    color: #0f172a;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  .doc-meta {
    font-size: 7.5px;
    color: #64748b;
    margin-top: 2px;
  }

  .kpi-row {
    width: 100%;
    margin-bottom: 12px;
    border-collapse: collapse;
  }
  .kpi-cell {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 5px 10px;
    font-size: 8px;
  }
  .kpi-title {
    font-size: 7px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: bold;
  }
  .kpi-val {
    font-size: 10px;
    font-weight: bold;
    color: #0f172a;
    margin-top: 1px;
  }

  .van-section {
    margin-bottom: 16px;
    page-break-inside: avoid;
  }
  .van-banner {
    background: #1e3a8a;
    color: #ffffff;
    padding: 5px 8px;
    font-size: 9.5px;
    font-weight: bold;
    border-radius: 3px 3px 0 0;
  }
  .van-banner table {
    width: 100%;
  }
  .van-banner td {
    color: #ffffff;
    font-size: 8.5px;
  }

  table.roster-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
    border-top: none;
  }
  table.roster-table th {
    background: #f1f5f9;
    color: #334155;
    font-size: 7.5px;
    font-weight: bold;
    text-transform: uppercase;
    padding: 4px 6px;
    border: 1px solid #cbd5e1;
    text-align: left;
  }
  table.roster-table td {
    border: 1px solid #e2e8f0;
    padding: 3.5px 6px;
    font-size: 7.8px;
  }
  table.roster-table tr:nth-child(even) {
    background: #fbfcfe;
  }

  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .font-bold { font-weight: bold; }
  .badge-drop {
    background: #fef3c7;
    color: #92400e;
    padding: 1px 4px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 7px;
    display: inline-block;
  }
  .badge-class {
    background: #e2e8f0;
    color: #1e293b;
    padding: 1px 4px;
    border-radius: 2px;
    font-weight: bold;
    font-size: 7.5px;
  }

  .subtotal-row {
    background: #f8fafc;
    font-weight: bold;
    font-size: 8px;
  }
  .subtotal-row td {
    border-top: 1.5px solid #cbd5e1;
  }

  .signatures {
    margin-top: 25px;
    width: 100%;
    page-break-inside: avoid;
  }
  .sign-col {
    text-align: center;
    width: 33.33%;
    padding-top: 25px;
    border-top: 1px dashed #94a3b8;
    font-size: 8px;
    font-weight: bold;
    color: #475569;
  }

  .footer {
    text-align: center;
    font-size: 7px;
    color: #94a3b8;
    margin-top: 15px;
    border-top: 1px solid #e2e8f0;
    padding-top: 4px;
  }
</style>
</head>
<body>

  {{-- Header --}}
  <div class="header">
    <div class="school-name">{{ $school?->school_name ?? 'DASA EPS SCHOOL' }}</div>
    <div class="school-sub">{{ $school?->address ?? 'School Transport & Fleet Operations Department' }} | Phone: {{ $school?->phone ?? '—' }}</div>
    <div class="doc-title">Official Fleet Passenger Manifest & Student Transport Roster</div>
    <div class="doc-meta">Academic Year: {{ now()->format('Y') }}-{{ now()->addYear()->format('y') }} &nbsp;|&nbsp; Generated on: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Filter: {{ $selectedVan === 'all' ? 'All 5 Fleet Vans' : $selectedVan }}</div>
  </div>

  {{-- Overall Summary Strip --}}
  <table class="kpi-row">
    <tr>
      <td class="kpi-cell" style="width: 25%;">
        <div class="kpi-title">Total Passengers</div>
        <div class="kpi-val">{{ number_format($totalStudents) }} Students</div>
      </td>
      <td class="kpi-cell" style="width: 25%;">
        <div class="kpi-title">Active Fleet Vans</div>
        <div class="kpi-val">{{ $selectedVan === 'all' ? '5 Vans (Van 1 to Van 5)' : ($vansMeta[$selectedVan]['title'] ?? $selectedVan) }}</div>
      </td>
      <td class="kpi-cell" style="width: 25%;">
        <div class="kpi-title">Monthly Transport Billing</div>
        <div class="kpi-val" style="color: #047857;">₹{{ number_format($totalFare, 2) }}</div>
      </td>
      <td class="kpi-cell" style="width: 25%;">
        <div class="kpi-title">Service Status</div>
        <div class="kpi-val" style="color: #1e40af;">Operational / Verified</div>
      </td>
    </tr>
  </table>

  {{-- Van-by-Van Roster Tables --}}
  @foreach($passengers as $vanKey => $vanStudents)
  @php
    $meta = $vansMeta[$vanKey] ?? ['title' => $vanKey, 'vehicle' => $vanKey, 'route_no' => $vanKey];
    $vanFareSum = $vanStudents->sum('amount');
  @endphp
  <div class="van-section">
    <div class="van-banner">
      <table>
        <tr>
          <td style="font-weight: bold; font-size: 9px;">
            {{ $meta['title'] }}
          </td>
          <td style="text-align: right; font-size: 8px;">
            Passengers: <strong>{{ $vanStudents->count() }}</strong> &nbsp;|&nbsp;
            Monthly Total: <strong>₹{{ number_format($vanFareSum, 2) }}</strong>
          </td>
        </tr>
      </table>
    </div>

    <table class="roster-table">
      <thead>
        <tr>
          <th style="width: 28px;" class="text-center">S.No</th>
          <th style="width: 140px;">Student Name</th>
          <th style="width: 50px;" class="text-center">Class</th>
          <th>Boarding Stop / Area</th>
          <th style="width: 75px;" class="text-center">Morning Pickup</th>
          <th style="width: 75px;" class="text-center">Evening Drop</th>
          <th style="width: 65px;" class="text-right">Monthly Fee</th>
        </tr>
      </thead>
      <tbody>
        @foreach($vanStudents as $student)
        @php
          $isDropOnly = in_array(strtolower(trim((string)$student->morning_time)), ['eve', 'evening', '-', '']);
        @endphp
        <tr>
          <td class="text-center font-bold" style="color: #64748b;">{{ $student->serial_no }}</td>
          <td class="font-bold" style="color: #0f172a;">{{ $student->student_name }}</td>
          <td class="text-center"><span class="badge-class">{{ $student->class_name }}</span></td>
          <td>{{ $student->area }}</td>
          <td class="text-center">
            @if($isDropOnly)
              <span class="badge-drop">Drop Only</span>
            @else
              {{ $student->morning_time }} AM
            @endif
          </td>
          <td class="text-center font-bold">{{ $student->evening_time }} PM</td>
          <td class="text-right font-bold" style="color: #047857;">₹{{ number_format($student->amount, 0) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="subtotal-row">
          <td colspan="3" style="text-align: left; padding: 4px 6px;">
            Subtotal ({{ $vanKey }}): <strong>{{ $vanStudents->count() }} Students</strong>
          </td>
          <td colspan="3" style="text-align: right; color: #475569; padding: 4px 6px;">
            Total Monthly Transport Fare for {{ $vanKey }}:
          </td>
          <td class="text-right font-bold" style="color: #047857; padding: 4px 6px;">
            ₹{{ number_format($vanFareSum, 2) }}
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  @endforeach

  {{-- Signatures Section --}}
  <table class="signatures">
    <tr>
      <td class="sign-col">
        Transport In-Charge / Fleet Manager
      </td>
      <td class="sign-col" style="margin: 0 15px;">
        Accounts & Billing Supervisor
      </td>
      <td class="sign-col">
        Principal / Authorized Signatory
      </td>
    </tr>
  </table>

  <div class="footer">
    This document is an official computer-generated passenger manifest of {{ $school?->school_name ?? 'DASA EPS SCHOOL' }}. All rights reserved.
  </div>

</body>
</html>
