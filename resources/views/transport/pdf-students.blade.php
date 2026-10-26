<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title ?? 'Student Transport Manifest' }}</title>
<style>
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #1e293b;
    margin: 15px;
  }
  .header {
    text-align: center;
    border-bottom: 2px solid #8C2826;
    padding-bottom: 8px;
    margin-bottom: 12px;
  }
  .header h1 {
    font-size: 16px;
    color: #8C2826;
    margin: 0;
    text-transform: uppercase;
  }
  .header p {
    font-size: 9px;
    color: #64748b;
    margin: 2px 0 0 0;
  }
  .meta-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 9px;
    color: #475569;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
  }
  th {
    background-color: #f1f5f9;
    color: #0f172a;
    font-weight: bold;
    text-align: left;
    padding: 6px 4px;
    border: 1px solid #cbd5e1;
    font-size: 8.5px;
    text-transform: uppercase;
  }
  td {
    padding: 5px 4px;
    border: 1px solid #cbd5e1;
    font-size: 8.5px;
  }
  tr:nth-child(even) {
    background-color: #f8fafc;
  }
  .badge-active {
    color: #15803d;
    font-weight: bold;
  }
  .badge-disc {
    color: #b91c1c;
    font-weight: bold;
  }
  .text-right {
    text-align: right;
  }
</style>
</head>
<body>
  <div class="header">
    <h1>Erode Public School – Transport Management</h1>
    <p>{{ $title ?? 'Vehicle-wise Student Passenger Manifest' }} | Generated: {{ $generated ?? now()->format('d M Y, h:i A') }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width: 25px;">#</th>
        <th>Admission #</th>
        <th>Student Name</th>
        <th>Class &amp; Sec</th>
        <th>Vehicle</th>
        <th>Route Corridor</th>
        <th>Pickup Stop</th>
        <th>Pickup Time</th>
        <th>Drop Stop</th>
        <th>Drop Time</th>
        <th class="text-right">Fee (₹)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $idx => $r)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $r['Admission No'] ?? '—' }}</strong></td>
          <td>{{ $r['Student Name'] ?? '—' }}</td>
          <td>{{ $r['Class & Section'] ?? '—' }}</td>
          <td><strong>{{ $r['Vehicle'] ?? '—' }}</strong></td>
          <td>{{ $r['Route'] ?? '—' }}</td>
          <td>{{ $r['Pickup Stop'] ?? '—' }}</td>
          <td>{{ $r['Pickup Time'] ?? '—' }}</td>
          <td>{{ $r['Drop Stop'] ?? '—' }}</td>
          <td>{{ $r['Drop Time'] ?? '—' }}</td>
          <td class="text-right">{{ number_format($r['Transport Fee'] ?? 0) }}</td>
          <td class="{{ ($r['Status'] ?? '') === 'Active' ? 'badge-active' : 'badge-disc' }}">{{ $r['Status'] ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="12" style="text-align:center; padding: 20px;">No records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
