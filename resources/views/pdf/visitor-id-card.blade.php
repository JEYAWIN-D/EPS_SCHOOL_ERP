<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visitor Pass — {{ $visitor->pass_number }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  
  @page {
    margin: 0;
    padding: 0;
    @if($orientation === 'portrait')
      size: 53.98mm 85.6mm;
    @else
      size: 85.6mm 53.98mm;
    @endif
  }

  html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 8px;
    color: #0f172a;
    background: #ffffff;
  }

  /* ═════════════════════════════════════════════════════════════════
     LANDSCAPE VISITOR PASS (85.6mm x 53.98mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-landscape {
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    border-radius: 3.5mm;
    overflow: hidden;
    background: #ffffff;
    border: 1.5px solid #b91c1c;
    position: relative;
    text-align: left;
    margin: 0;
    page-break-inside: avoid;
    page-break-after: avoid;
  }

  .v-security-band {
    height: 4mm;
    background: #b91c1c;
    color: #ffffff;
    font-size: 5.5px;
    font-weight: bold;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-align: center;
    line-height: 4mm;
  }

  .v-header {
    height: 10mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1mm 2.5mm;
    border-bottom: 1.5px solid #d97706;
  }
  .v-header-table { width: 100%; border-collapse: collapse; }
  .v-logo-cell { width: 8.5mm; vertical-align: middle; }
  .v-logo-img { width: 8mm; height: 8mm; }
  .v-title-cell { vertical-align: middle; padding-left: 2mm; }
  .v-school-name { font-size: 7.2px; font-weight: bold; text-transform: uppercase; color: #ffffff; line-height: 1.1; }
  .v-pass-badge { font-size: 5.2px; color: #fef08a; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

  .v-body-table { width: 100%; border-collapse: collapse; padding: 2mm 2.5mm; }
  .v-photo-cell { width: 22mm; vertical-align: top; text-align: center; padding-right: 2mm; }
  .v-photo-img {
    width: 20mm;
    height: 23mm;
    object-fit: cover;
    border-radius: 2mm;
    border: 1.5px solid #b91c1c;
    background: #f1f5f9;
  }
  .v-photo-placeholder {
    width: 20mm;
    height: 23mm;
    border-radius: 2mm;
    border: 1.5px dashed #94a3b8;
    background: #fef2f2;
    text-align: center;
    line-height: 23mm;
    font-size: 6.5px;
    color: #b91c1c;
    font-weight: bold;
  }
  .v-pass-num {
    margin-top: 1mm;
    background: #fee2e2;
    border: 1px solid #f87171;
    border-radius: 2mm;
    font-size: 5.5px;
    font-weight: bold;
    padding: 0.5mm 1mm;
    color: #991b1b;
    text-align: center;
  }

  .v-info-cell { vertical-align: top; }
  .v-visitor-name {
    font-size: 8.5px;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.15;
    margin-bottom: 0.5mm;
    text-transform: uppercase;
  }
  .v-company-name {
    font-size: 6px;
    color: #64748b;
    font-weight: bold;
    margin-bottom: 1mm;
  }

  .v-detail-table { width: 100%; border-collapse: collapse; font-size: 6px; }
  .v-detail-table td { padding: 0.3mm 0; line-height: 1.15; }
  .v-lbl { width: 17mm; color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 5.2px; }
  .v-val { color: #0f172a; font-weight: bold; }

  .v-qr-cell { width: 14mm; vertical-align: middle; text-align: right; padding-left: 1mm; }
  .v-qr-img { width: 13mm; height: 13mm; }

  .v-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 5.5mm;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 0.8mm 2.5mm;
    font-size: 5.2px;
    color: #475569;
  }

  /* ═════════════════════════════════════════════════════════════════
     PORTRAIT VISITOR PASS (53.98mm x 85.6mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-portrait {
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    border-radius: 3.5mm;
    overflow: hidden;
    background: #ffffff;
    border: 1.5px solid #b91c1c;
    position: relative;
    text-align: center;
    margin: 0;
    page-break-inside: avoid;
    page-break-after: avoid;
  }

  .vp-band {
    height: 4mm;
    background: #b91c1c;
    color: #ffffff;
    font-size: 5.5px;
    font-weight: bold;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    text-align: center;
    line-height: 4mm;
  }

  .vp-header {
    height: 13mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1.2mm 2mm;
    border-bottom: 1.5px solid #d97706;
    text-align: center;
  }
  .vp-logo-img { width: 6.5mm; height: 6.5mm; margin: 0 auto 0.3mm auto; }
  .vp-school-name { font-size: 6.5px; font-weight: bold; text-transform: uppercase; color: #ffffff; line-height: 1.1; }

  .vp-photo-wrap { margin-top: 2mm; text-align: center; }
  .vp-photo-img {
    width: 20mm;
    height: 24mm;
    object-fit: cover;
    border-radius: 2mm;
    border: 1.5px solid #b91c1c;
    margin: 0 auto;
    background: #f1f5f9;
  }
  .vp-photo-placeholder {
    width: 20mm;
    height: 24mm;
    border-radius: 2mm;
    border: 1.5px dashed #94a3b8;
    background: #fef2f2;
    margin: 0 auto;
    line-height: 24mm;
    font-size: 6.5px;
    color: #b91c1c;
    font-weight: bold;
  }

  .vp-visitor-name {
    font-size: 8px;
    font-weight: bold;
    color: #0f172a;
    margin-top: 1mm;
    text-transform: uppercase;
    line-height: 1.15;
    padding: 0 1.5mm;
  }
  .vp-pass-num {
    display: inline-block;
    background: #fee2e2;
    border: 1px solid #f87171;
    border-radius: 2mm;
    font-size: 5.5px;
    font-weight: bold;
    padding: 0.3mm 1.5mm;
    color: #991b1b;
    margin-top: 0.5mm;
  }

  .vp-details-box {
    margin: 2mm 2mm;
    text-align: left;
    font-size: 5.6px;
  }
  .vp-detail-table { width: 100%; border-collapse: collapse; }
  .vp-detail-table td { padding: 0.3mm 0; line-height: 1.15; }
  .vp-lbl { width: 15mm; color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 5px; }
  .vp-val { color: #0f172a; font-weight: bold; }

  .vp-bottom-table {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 11mm;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 1mm 2mm;
    width: 100%;
    border-collapse: collapse;
  }
</style>
</head>
<body>

@if($orientation === 'landscape')
<div class="card-landscape">
  <div class="v-security-band">★ OFFICIAL CAMPUS VISITOR PASS ★</div>

  <div class="v-header">
    <table class="v-header-table">
      <tr>
        <td class="v-logo-cell">
          <img src="{{ public_path('images/school-logo.png') }}" class="v-logo-img" alt="Logo">
        </td>
        <td class="v-title-cell">
          <div class="v-school-name">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
          <div class="v-pass-badge">SECURITY CLEARANCE PASS</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="v-body-table">
    <tr>
      <td class="v-photo-cell">
        @if(!empty($visitor->visitor_photo) && file_exists(storage_path('app/public/' . $visitor->visitor_photo)))
          <img src="{{ storage_path('app/public/' . $visitor->visitor_photo) }}" class="v-photo-img" alt="Photo">
        @else
          <div class="v-photo-placeholder">VISITOR</div>
        @endif
        <div class="v-pass-num">{{ $visitor->pass_number ?: 'PASS-' . $visitor->id }}</div>
      </td>

      <td class="v-info-cell">
        <div class="v-visitor-name">{{ $visitor->visitor_name }}</div>
        @if($visitor->company_name)
          <div class="v-company-name">{{ $visitor->company_name }}</div>
        @endif
        <table class="v-detail-table">
          <tr>
            <td class="v-lbl">Host / Contact:</td>
            <td class="v-val">{{ $visitor->whom_to_meet ?? $visitor->hostEmployee?->full_name ?? 'School Office' }}</td>
          </tr>
          <tr>
            <td class="v-lbl">Purpose:</td>
            <td class="v-val">{{ Str::limit($visitor->purpose, 30) }}</td>
          </tr>
          <tr>
            <td class="v-lbl">Entry Time:</td>
            <td class="v-val">{{ $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('d/m/Y h:i A') : date('d/m/Y h:i A') }}</td>
          </tr>
          <tr>
            <td class="v-lbl">Pass Expiry:</td>
            <td class="v-val" style="color: #b91c1c;">{{ $visitor->expected_exit_time ? \Carbon\Carbon::parse($visitor->expected_exit_time)->format('h:i A (Same Day)') : 'Same Day Dispersal' }}</td>
          </tr>
        </table>
      </td>

      @if(!empty($barcodeSvg))
      <td style="width: 25mm; vertical-align: middle; text-align: right; padding-left: 1mm;">
        <div style="height: 6mm; overflow: hidden;">{!! $barcodeSvg !!}</div>
        <div style="font-size: 4px; font-family: monospace; color: #64748b; text-align: center;">* {{ $barcodeVal ?? $card->card_number }} *</div>
      </td>
      @endif
    </tr>
  </table>

  <div class="v-footer">
    <table style="width: 100%; border-collapse: collapse;">
      <tr>
        <td>Badge #: {{ $card->card_number }} &bull; Please return to gate on exit</td>
        <td style="text-align: right; font-weight: bold; color: #b91c1c;">Gate Security Officer</td>
      </tr>
    </table>
  </div>
</div>

@else
{{-- Portrait Layout --}}
<div class="card-portrait">
  <div class="vp-band">★ OFFICIAL VISITOR PASS ★</div>

  <div class="vp-header">
    <img src="{{ public_path('images/school-logo.png') }}" class="vp-logo-img" alt="Logo">
    <div class="vp-school-name">{{ $config['school_name'] ?? 'Erode Public School' }}</div>
  </div>

  <div class="vp-photo-wrap">
    @if(!empty($visitor->visitor_photo) && file_exists(storage_path('app/public/' . $visitor->visitor_photo)))
      <img src="{{ storage_path('app/public/' . $visitor->visitor_photo) }}" class="vp-photo-img" alt="Photo">
    @else
      <div class="vp-photo-placeholder">VISITOR</div>
    @endif
  </div>

  <div class="vp-visitor-name">{{ $visitor->visitor_name }}</div>
  <div class="vp-pass-num">{{ $visitor->pass_number ?: 'PASS-' . $visitor->id }}</div>

  <div class="vp-details-box">
    <table class="vp-detail-table">
      <tr>
        <td class="vp-lbl">Whom to Meet:</td>
        <td class="vp-val">{{ $visitor->whom_to_meet ?? 'School Office' }}</td>
      </tr>
      <tr>
        <td class="vp-lbl">Purpose:</td>
        <td class="vp-val">{{ Str::limit($visitor->purpose, 25) }}</td>
      </tr>
      <tr>
        <td class="vp-lbl">In Time:</td>
        <td class="vp-val">{{ $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('h:i A') : date('h:i A') }}</td>
      </tr>
      <tr>
        <td class="vp-lbl">Valid Till:</td>
        <td class="vp-val" style="color: #b91c1c;">{{ $visitor->expected_exit_time ? \Carbon\Carbon::parse($visitor->expected_exit_time)->format('h:i A') : 'Same Day' }}</td>
      </tr>
    </table>
  </div>

  <table class="vp-bottom-table">
    <tr>
      <td style="vertical-align: middle; text-align: center;">
        @if(!empty($barcodeSvg))
          <div style="height: 4.5mm; overflow: hidden; margin: 0 auto; text-align: center;">{!! $barcodeSvg !!}</div>
        @endif
        <div style="font-size: 4px; font-family: monospace; color: #64748b;">* {{ $barcodeVal ?? $card->card_number }} * &bull; Gate Security Pass</div>
      </td>
    </tr>
  </table>
</div>
@endif

</body>
</html>
