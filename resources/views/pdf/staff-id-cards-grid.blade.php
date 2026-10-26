<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
@php
  $config = $template?->configuration_json ?? [];
  $side = $side ?? 'both';
  $orientation = $orientation ?? 'landscape';
@endphp
<title>Staff Identity Cards — Erode Public School</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 7.5px;
    color: #0f172a;
    background: #ffffff;
  }

  @page {
    @if($orientation === 'portrait')
      size: A4 portrait;
      margin: 8mm 6mm;
    @else
      size: A4 landscape;
      margin: 8mm 6mm;
    @endif
  }

  .page-container {
    width: 100%;
    margin: 0 auto;
    page-break-after: always;
  }
  .page-container:last-child {
    page-break-after: avoid;
  }

  .card-grid-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 4mm 4mm;
    margin: 0 auto;
  }

  .card-grid-cell {
    vertical-align: top;
    text-align: center;
  }

  /* ═════════════════════════════════════════════════════════════════
     LANDSCAPE STAFF CR80 (85.6mm x 53.98mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-landscape {
    width: 85.6mm;
    height: 53.98mm;
    border-radius: 4mm;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    page-break-inside: avoid;
    position: relative;
    text-align: left;
    margin: 0 auto;
  }

  .l-header {
    height: 12.5mm;
    background: {{ $config['primary_color'] ?? '#892528' }};
    color: #ffffff;
    padding: 1.5mm 2.5mm;
    border-bottom: 1.5px solid {{ $config['accent_gold'] ?? '#C59B27' }};
  }
  .l-header-table { width: 100%; border-collapse: collapse; }
  .l-logo-cell { width: 9mm; vertical-align: middle; }
  .l-logo-img { width: 8.5mm; height: 8.5mm; }
  .l-title-cell { vertical-align: middle; padding-left: 2mm; }
  .l-school-name { font-size: 7.5px; font-weight: bold; text-transform: uppercase; color: #ffffff; letter-spacing: 0.3px; line-height: 1.1; }
  .l-school-sub { font-size: 5.2px; color: #fef08a; letter-spacing: 0.2px; line-height: 1.1; margin-top: 0.5px; }

  .l-body-table { width: 100%; border-collapse: collapse; padding: 1.5mm 2.5mm; }
  .l-photo-cell { width: 29mm; vertical-align: top; text-align: center; padding-right: 2mm; }
  .l-photo-img {
    width: 27mm;
    height: 32mm;
    object-fit: cover;
    border-radius: 2mm;
    border: 1.5px solid #892528;
    background: #f1f5f9;
  }
  .l-photo-placeholder {
    width: 27mm;
    height: 32mm;
    border-radius: 2mm;
    border: 1.5px dashed #94a3b8;
    background: #f8fafc;
    text-align: center;
    line-height: 32mm;
    font-size: 6.5px;
    color: #64748b;
    font-weight: bold;
  }
  .l-emp-badge {
    margin-top: 0.8mm;
    background: #0f172a;
    border-radius: 1.5mm;
    font-size: 5.8px;
    font-weight: bold;
    padding: 0.5mm 1mm;
    color: #fbbf24;
    text-align: center;
    font-family: DejaVu Sans, monospace;
  }

  .l-info-cell { vertical-align: top; padding-left: 1mm; }
  .l-emp-name {
    font-size: 8.5px;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.15;
    margin-bottom: 0.8mm;
    text-transform: uppercase;
  }
  .l-role-badge {
    display: inline-block;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 1.5mm;
    font-size: 5.5px;
    font-weight: bold;
    padding: 0.3mm 1.5mm;
    color: #892528;
    margin-bottom: 1.2mm;
  }
  .l-detail-table { width: 100%; border-collapse: collapse; font-size: 5.8px; }
  .l-detail-table td { padding: 0.35mm 0; line-height: 1.15; }
  .l-lbl { width: 16mm; color: #475569; font-weight: bold; text-transform: uppercase; font-size: 5.2px; }
  .l-val { color: #0f172a; font-weight: bold; }

  .l-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 6.8mm;
    background: #ffffff;
    border-top: 1px solid #cbd5e1;
    padding: 0.5mm 2.5mm;
  }
  .l-footer-table { width: 100%; border-collapse: collapse; font-size: 4.8px; color: #475569; }

  /* ═════════════════════════════════════════════════════════════════
     PORTRAIT STAFF CR80 (53.98mm x 85.6mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-portrait {
    width: 53.98mm;
    height: 85.6mm;
    border-radius: 3.5mm;
    overflow: hidden;
    background: #ffffff;
    border: 1.5px solid #0f172a;
    page-break-inside: avoid;
    position: relative;
    text-align: center;
    margin: 0 auto;
  }

  .p-header {
    height: 14mm;
    background: #892528;
    color: #ffffff;
    padding: 1.2mm 2mm;
    border-bottom: 1.5px solid #c59b27;
    text-align: center;
  }
  .p-logo-img { width: 7.5mm; height: 7.5mm; margin: 0 auto 0.3mm auto; }
  .p-school-name { font-size: 6.8px; font-weight: bold; text-transform: uppercase; color: #ffffff; letter-spacing: 0.2px; line-height: 1.1; }
  .p-school-sub { font-size: 4.6px; color: #fef08a; letter-spacing: 0.1px; margin-top: 0.2mm; }

  .p-photo-wrap { margin-top: 1.8mm; text-align: center; }
  .p-photo-img {
    width: 26mm;
    height: 32mm;
    object-fit: cover;
    border-radius: 2mm;
    border: 1.5px solid #892528;
    margin: 0 auto;
    background: #f1f5f9;
  }
  .p-photo-placeholder {
    width: 26mm;
    height: 32mm;
    border-radius: 2mm;
    border: 1.5px dashed #94a3b8;
    background: #f8fafc;
    margin: 0 auto;
    line-height: 32mm;
    font-size: 6.5px;
    color: #64748b;
    font-weight: bold;
  }

  .p-emp-name {
    font-size: 8px;
    font-weight: bold;
    color: #0f172a;
    margin-top: 1.2mm;
    text-transform: uppercase;
    line-height: 1.15;
    padding: 0 1.5mm;
  }
  .p-emp-badge {
    display: inline-block;
    background: #0f172a;
    border-radius: 1.5mm;
    font-size: 5.8px;
    font-weight: bold;
    padding: 0.4mm 1.8mm;
    color: #fbbf24;
    margin-top: 0.6mm;
    font-family: DejaVu Sans, monospace;
  }

  .p-details-box {
    margin: 1.5mm 2mm;
    text-align: left;
    font-size: 5.4px;
  }
  .p-detail-table { width: 100%; border-collapse: collapse; }
  .p-detail-table td { padding: 0.3mm 0; line-height: 1.15; }
  .p-lbl { width: 15mm; color: #475569; font-weight: bold; text-transform: uppercase; font-size: 4.8px; }
  .p-val { color: #0f172a; font-weight: bold; }

  .p-bottom-table {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 9mm;
    background: #ffffff;
    border-top: 1px solid #cbd5e1;
    padding: 0.5mm 2mm;
    width: 100%;
    border-collapse: collapse;
  }

  .card-back {
    background: #ffffff;
    padding: 2.5mm 3mm;
    text-align: left;
  }
  .back-school-title {
    font-size: 7.2px;
    font-weight: bold;
    color: {{ $config['primary_color'] ?? '#892528' }};
    text-transform: uppercase;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1mm;
    margin-bottom: 1.5mm;
  }
  .back-terms {
    font-size: 5.2px;
    color: #334155;
    line-height: 1.25;
    white-space: pre-line;
  }
  .back-contact-box {
    margin-top: 1.5mm;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 2mm;
    padding: 1mm 1.5mm;
    font-size: 5px;
    color: #475569;
    text-align: center;
    line-height: 1.2;
  }
</style>
</head>
<body>

@php
  $cardsPerPage = ($orientation === 'landscape') ? 6 : 8;
  $columnsCount = ($orientation === 'landscape') ? 3 : 2;
  $pages = array_chunk($cardsData, $cardsPerPage);
@endphp

@foreach($pages as $pageCards)
<div class="page-container">
  <table class="card-grid-table">
    @foreach(array_chunk($pageCards, $columnsCount) as $rowCards)
    <tr>
      @foreach($rowCards as $item)
      @php
        $emp = $item['employee'];
        $card = $item['card'];
        $empCode = $emp->employee_code ?: ('EMP-' . str_pad($emp->id, 3, '0', STR_PAD_LEFT));
        $barcodeVal = $item['barcodeVal'] ?? ($card->barcode_value ?: ('EPS-' . $empCode));
        $barcodeSvg = $item['barcodeSvg'] ?? null;
        $deptName = $emp->department?->name ?? $emp->department ?? 'General';
        $designation = $emp->designation ?? 'Faculty';
        $individualPhone = $emp->mobile ?: ($emp->alternate_phone ?: ($emp->emergency_contact_mobile ?: ($emp->contact_no ?: '—')));
        $individualAddress = $emp->residential_address ?: ($emp->permanent_address ?: ($emp->address ?: 'Erode, Tamil Nadu'));
      @endphp
      <td class="card-grid-cell">

        @if($orientation === 'landscape')
        <div class="card-landscape">
          {{-- Header --}}
          <div class="l-header">
            <table class="l-header-table">
              <tr>
                <td class="l-logo-cell">
                  <img src="{{ public_path('images/school-logo.png') }}" class="l-logo-img" alt="Logo">
                </td>
                <td class="l-title-cell">
                  <div class="l-school-name">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
                  <div class="l-school-sub">FACULTY CREDENTIAL &bull; CBSE AFF NO. 1931585</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                  <span style="font-size: 5px; font-weight: bold; background: #0f172a; color: #34d399; padding: 0.4mm 1.2mm; border-radius: 1mm; border: 0.5px solid #10b981;">
                    PRESENT
                  </span>
                </td>
              </tr>
            </table>
          </div>

          {{-- Body --}}
          <table class="l-body-table">
            <tr>
              {{-- Photo + Emp Badge --}}
              <td class="l-photo-cell">
                @if(!empty($emp->photo) && file_exists(storage_path('app/public/' . $emp->photo)))
                  <img src="{{ storage_path('app/public/' . $emp->photo) }}" class="l-photo-img" alt="Photo">
                @else
                  <div class="l-photo-placeholder">PHOTO</div>
                @endif
                <div class="l-emp-badge">{{ $empCode }}</div>
              </td>

              {{-- Details --}}
              <td class="l-info-cell">
                <div class="l-emp-name">{{ $emp->full_name }}</div>
                <div class="l-role-badge">{{ $designation }} &bull; {{ $deptName }}</div>
                <table class="l-detail-table">
                  <tr>
                    <td class="l-lbl">STATUS:</td>
                    <td class="l-val" style="color: #10b981;">ACTIVE FACULTY (PRESENT)</td>
                  </tr>
                  <tr>
                    <td class="l-lbl">PHONE:</td>
                    <td class="l-val">{{ $individualPhone }}</td>
                  </tr>
                  <tr>
                    <td class="l-lbl">ADDRESS:</td>
                    <td class="l-val" style="font-size: 5px;">{{ Str::limit($individualAddress, 36) }}</td>
                  </tr>
                  @if(!empty($emp->blood_group) || !empty($emp->joining_date))
                  <tr>
                    <td class="l-lbl">METRICS:</td>
                    <td class="l-val" style="font-size: 5px;">
                      @if(!empty($emp->blood_group)) Blood: {{ $emp->blood_group }} @endif
                      @if(!empty($emp->joining_date)) &bull; Joined: {{ $emp->joining_date->format('d/m/Y') }} @endif
                    </td>
                  </tr>
                  @endif
                </table>
              </td>
            </tr>
          </table>

          {{-- Footer --}}
          <div class="l-footer">
            <table class="l-footer-table">
              <tr>
                <td style="vertical-align: middle; width: 44mm;">
                  @if($barcodeSvg)
                    <div style="height: 4.5mm; overflow: hidden;">{!! $barcodeSvg !!}</div>
                  @endif
                  <div style="font-size: 4px; font-family: monospace; color: #64748b; letter-spacing: 0.5px;">* {{ $barcodeVal }} *</div>
                </td>
                <td style="text-align: right; font-weight: bold; color: #892528; vertical-align: middle;">Authorized Signatory</td>
              </tr>
            </table>
          </div>
        </div>

        @else
        {{-- Portrait Card Layout --}}
        <div class="card-portrait">
          <div class="p-header">
            <table style="width: 100%; border-collapse: collapse;">
              <tr>
                <td style="width: 8mm; vertical-align: middle; text-align: left;">
                  <img src="{{ public_path('images/school-logo.png') }}" class="p-logo-img" alt="Logo">
                </td>
                <td style="vertical-align: middle; text-align: center;">
                  <div class="p-school-name">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
                  <div class="p-school-sub">FACULTY CREDENTIAL</div>
                </td>
              </tr>
            </table>
          </div>

          <div class="p-photo-wrap">
            @if(!empty($emp->photo) && file_exists(storage_path('app/public/' . $emp->photo)))
              <img src="{{ storage_path('app/public/' . $emp->photo) }}" class="p-photo-img" alt="Photo">
            @else
              <div class="p-photo-placeholder">PHOTO</div>
            @endif
          </div>

          <div class="p-emp-name">{{ $emp->full_name }}</div>
          <div class="p-emp-badge">{{ $empCode }}</div>

          <div class="p-details-box">
            <table class="p-detail-table">
              <tr>
                <td class="p-lbl">ROLE:</td>
                <td class="p-val" style="color: #892528;">{{ $designation }}</td>
              </tr>
              <tr>
                <td class="p-lbl">DEPT:</td>
                <td class="p-val">{{ $deptName }}</td>
              </tr>
              <tr>
                <td class="p-lbl">STATUS:</td>
                <td class="p-val">ACTIVE (PRESENT)</td>
              </tr>
              <tr>
                <td class="p-lbl">PHONE:</td>
                <td class="p-val">{{ $individualPhone }}</td>
              </tr>
              <tr>
                <td class="p-lbl">ADDRESS:</td>
                <td class="p-val" style="font-size: 4.8px;">{{ Str::limit($individualAddress, 28) }}</td>
              </tr>
              @if(!empty($emp->blood_group))
              <tr>
                <td class="p-lbl">BLOOD:</td>
                <td class="p-val">{{ $emp->blood_group }}</td>
              </tr>
              @endif
            </table>
          </div>

          <table class="p-bottom-table">
            <tr>
              <td style="vertical-align: middle; text-align: center;">
                @if($barcodeSvg)
                  <div style="height: 4.5mm; overflow: hidden; margin: 0 auto; text-align: center;">{!! $barcodeSvg !!}</div>
                @endif
                <div style="font-size: 4px; font-family: monospace; color: #64748b;">* {{ $barcodeVal }} * &bull; Authorized Signatory</div>
              </td>
            </tr>
          </table>
        </div>
        @endif

      </td>
      @endforeach

      @for($i = count($rowCards); $i < $columnsCount; $i++)
        <td class="card-grid-cell" style="visibility: hidden;"></td>
      @endfor
    </tr>
    @endforeach
  </table>
</div>

@if($side === 'both')
<div class="page-container">
  <table class="card-grid-table">
    @foreach(array_chunk($pageCards, $columnsCount) as $rowCards)
    <tr>
      @foreach($rowCards as $item)
      @php
        $emp = $item['employee'];
        $card = $item['card'];
      @endphp
      <td class="card-grid-cell">
        @if($orientation === 'landscape')
        <div class="card-landscape card-back">
          <div class="back-school-title">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
          <div class="back-terms">{{ $config['terms_text'] ?? "1. Official faculty card.\n2. Non-transferable.\n3. Return if found." }}</div>
          <div class="back-contact-box">
            <strong>Campus Address:</strong> {{ $config['school_address'] ?? '420, Vakkil Thottam, Manickam Palayam, Erode, TN' }}<br>
            <strong>HR Helpline:</strong> {{ $config['school_phone'] ?? '93677 54654' }}
          </div>
        </div>
        @else
        <div class="card-portrait card-back">
          <div class="back-school-title" style="font-size: 6.5px;">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
          <div class="back-terms" style="font-size: 4.8px; margin-top: 2mm;">{{ $config['terms_text'] ?? "1. Official faculty card.\n2. Non-transferable.\n3. Return if found." }}</div>
          <div class="back-contact-box" style="margin-top: 4mm; font-size: 4.5px;">
            {{ $config['school_address'] ?? 'Erode, Tamil Nadu – 638004' }}<br>
            Ph: {{ $config['school_phone'] ?? '93677 54654' }}
          </div>
        </div>
        @endif
      </td>
      @endforeach

      @for($i = count($rowCards); $i < $columnsCount; $i++)
        <td class="card-grid-cell" style="visibility: hidden;"></td>
      @endfor
    </tr>
    @endforeach
  </table>
</div>
@endif

@endforeach

</body>
</html>
