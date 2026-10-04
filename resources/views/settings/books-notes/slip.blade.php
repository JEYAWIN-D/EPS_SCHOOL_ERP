<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book, Note & Uniform Issuance Slip — {{ $data['student']['name'] }} ({{ $data['student']['admission_no'] }})</title>
  <style>
    @page { size: A4; margin: 15mm; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 20px; background: #fff; }
    .header { text-align: center; border-bottom: 2px solid #8C2826; padding-bottom: 12px; margin-bottom: 16px; }
    .school-name { font-size: 20px; font-weight: 900; color: #8C2826; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
    .school-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 4px; }
    .slip-title { display: inline-block; background: #FFF5F5; color: #8C2826; border: 1px solid #FECACA; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
    
    .student-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 18px; }
    .meta-item { font-size: 11px; }
    .meta-label { color: #64748b; font-weight: 600; display: block; font-size: 10px; text-transform: uppercase; }
    .meta-val { color: #0f172a; font-weight: 700; margin-top: 2px; }

    .summary-ribbon { display: flex; justify-content: space-between; background: #f1f5f9; padding: 8px 12px; border-radius: 6px; font-weight: 700; font-size: 11px; margin-bottom: 16px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; text-align: left; }
    td { border: 1px solid #e2e8f0; padding: 6px 8px; font-size: 11px; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-mono { font-family: monospace; font-size: 10px; }
    
    .badge-issued { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; }
    .badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; }
    .badge-paid { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; }
    .badge-unpaid { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; }

    .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 36px; padding-top: 16px; text-align: center; }
    .sig-line { border-top: 1px dashed #94a3b8; padding-top: 6px; font-weight: 700; font-size: 11px; color: #334155; }

    .print-btn { background: #8C2826; color: #fff; border: none; padding: 8px 16px; font-size: 12px; font-weight: bold; border-radius: 6px; cursor: pointer; margin-bottom: 16px; }
    @media print {
      .print-btn { display: none; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

  <button class="print-btn" onclick="window.print()">🖨️ Print Slip / Gate Pass</button>

  <div class="header">
    <h1 class="school-name">Erode Public School CBSE</h1>
    <div class="school-sub">Senior Secondary (Affiliation No. CBSE/1930XXX) • Chennimalai Road, Erode</div>
    <div class="slip-title">Book, Note &amp; Uniform Distribution &amp; Issuance Slip</div>
  </div>

  <div class="student-grid">
    <div class="meta-item">
      <span class="meta-label">Student Name</span>
      <span class="meta-val">{{ $data['student']['name'] }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Admission No</span>
      <span class="meta-val">{{ $data['student']['admission_no'] }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Class &amp; Section</span>
      <span class="meta-val">{{ $data['student']['class_name'] }} {{ $data['student']['section_name'] }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Gender</span>
      <span class="meta-val">{{ ucfirst($data['student']['gender'] ?? 'N/A') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Fee / Payment Status</span>
      <span class="meta-val">
        <span class="{{ ($data['student']['payment_status'] ?? '') === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
          {{ $data['student']['payment_status_label'] ?? 'Pending Payment' }}
        </span>
      </span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Date of Issuance</span>
      <span class="meta-val">{{ date('d M Y, h:i A') }}</span>
    </div>
  </div>

  <div class="summary-ribbon">
    <span>Total Prescribed Units: {{ $data['summary']['total_prescribed'] }}</span>
    <span style="color: #047857;">Total Units Issued: {{ $data['summary']['total_issued'] }}</span>
    <span style="color: {{ $data['summary']['total_remaining'] > 0 ? '#b45309' : '#047857' }};">
      Pending / Remaining Units: {{ $data['summary']['total_remaining'] }}
    </span>
    <span>Distribution: <strong>{{ $data['summary']['distribution_status'] ?? strtoupper(str_replace('_', ' ', $data['summary']['status'])) }}</strong></span>
  </div>

  {{-- Section 1: Prescribed Textbooks --}}
  <h3 style="font-size: 12px; font-weight: 800; color: #8C2826; text-transform: uppercase; margin-bottom: 6px;">1. Prescribed Textbooks (Books)</h3>
  <table>
    <thead>
      <tr>
        <th class="text-center" style="width: 30px;">#</th>
        <th style="width: 130px;">SKU / Code</th>
        <th>Book Title</th>
        <th class="text-center" style="width: 70px;">Prescribed</th>
        <th class="text-center" style="width: 70px;">Issued</th>
        <th class="text-center" style="width: 70px;">Remaining</th>
        <th class="text-center" style="width: 80px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($data['books'] as $idx => $b)
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td class="font-mono">{{ $b['sku'] ?? 'N/A' }}</td>
          <td style="font-weight: 600;">{{ $b['item_name'] }}</td>
          <td class="text-center font-mono">{{ $b['quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: #047857;">{{ $b['issued_quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: {{ $b['remaining_quantity'] > 0 ? '#b45309' : '#64748b' }};">{{ $b['remaining_quantity'] }}</td>
          <td class="text-center">
            @if($b['remaining_quantity'] === 0)
              <span class="badge-issued">ISSUED</span>
            @else
              <span class="badge-pending">PENDING</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center" style="color: #64748b;">No textbooks prescribed.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Section 2: Notebooks & Workbooks --}}
  <h3 style="font-size: 12px; font-weight: 800; color: #3730a3; text-transform: uppercase; margin-bottom: 6px; margin-top: 16px;">2. Notebooks &amp; Activity Journals</h3>
  <table>
    <thead>
      <tr>
        <th class="text-center" style="width: 30px;">#</th>
        <th style="width: 130px;">SKU / Code</th>
        <th>Notebook Description</th>
        <th class="text-center" style="width: 70px;">Prescribed</th>
        <th class="text-center" style="width: 70px;">Issued</th>
        <th class="text-center" style="width: 70px;">Remaining</th>
        <th class="text-center" style="width: 80px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($data['notes'] as $idx => $n)
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td class="font-mono">{{ $n['sku'] ?? 'N/A' }}</td>
          <td style="font-weight: 600;">{{ $n['item_name'] }}</td>
          <td class="text-center font-mono">{{ $n['quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: #047857;">{{ $n['issued_quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: {{ $n['remaining_quantity'] > 0 ? '#b45309' : '#64748b' }};">{{ $n['remaining_quantity'] }}</td>
          <td class="text-center">
            @if($n['remaining_quantity'] === 0)
              <span class="badge-issued">ISSUED</span>
            @else
              <span class="badge-pending">PENDING</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center" style="color: #64748b;">No notebooks prescribed.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Section 3: Prescribed Uniforms --}}
  <h3 style="font-size: 12px; font-weight: 800; color: #065f46; text-transform: uppercase; margin-bottom: 6px; margin-top: 16px;">3. Prescribed School Uniform</h3>
  <table>
    <thead>
      <tr>
        <th class="text-center" style="width: 30px;">#</th>
        <th style="width: 130px;">SKU / Code</th>
        <th>Uniform Article</th>
        <th class="text-center" style="width: 70px;">Prescribed</th>
        <th class="text-center" style="width: 70px;">Issued</th>
        <th class="text-center" style="width: 70px;">Remaining</th>
        <th class="text-center" style="width: 80px;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($data['uniforms'] ?? [] as $idx => $u)
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td class="font-mono">{{ $u['sku'] ?? 'N/A' }}</td>
          <td style="font-weight: 600;">{{ $u['item_name'] }}</td>
          <td class="text-center font-mono">{{ $u['quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: #047857;">{{ $u['issued_quantity'] }}</td>
          <td class="text-center font-mono" style="font-weight: 800; color: {{ $u['remaining_quantity'] > 0 ? '#b45309' : '#64748b' }};">{{ $u['remaining_quantity'] }}</td>
          <td class="text-center">
            @if($u['remaining_quantity'] === 0)
              <span class="badge-issued">ISSUED</span>
            @else
              <span class="badge-pending">PENDING</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center" style="color: #64748b;">No uniforms allocated.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if($data['summary']['total_remaining'] > 0)
    <div style="background: #fffbeb; border: 1px dashed #f59e0b; padding: 10px; border-radius: 6px; font-size: 11px; color: #92400e; margin-top: 14px;">
      <strong>Notice regarding pending items:</strong> The {{ $data['summary']['total_remaining'] }} remaining units marked as "PENDING" above will be issued upon arrival of fresh stock. Please present this slip at the Store Counter for collection.
    </div>
  @endif

  <div class="signatures">
    <div class="sig-line">Parent / Student Signature</div>
    <div class="sig-line">Store In-Charge / Issuer</div>
    <div class="sig-line">Principal / Authorised Signatory</div>
  </div>

</body>
</html>
