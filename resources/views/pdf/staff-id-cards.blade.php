<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff ID Cards — {{ ucfirst(str_replace('_', ' ', $categoryFilter ?? 'all')) }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 7.5px;
    color: #0f172a;
    background: #ffffff;
  }

  @page {
    @if(($style ?? 'landscape') === 'portrait')
      size: A4 portrait;
      margin: 8mm 6mm;
    @else
      size: A4 landscape;
      margin: 8mm 6mm;
    @endif
  }

  .print-page {
    width: 100%;
    margin: 0 auto;
  }

  .card-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 5mm 4mm;
    margin: 0 auto;
  }

  .card-cell {
    vertical-align: top;
    text-align: center;
  }

  /* ═════════════════════════════════════════════════════════════════
     LANDSCAPE CARD STYLING (85.6mm x 54mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-landscape {
    width: 85.6mm;
    height: 54mm;
    border-radius: 4.5mm;
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
    color: #ffffff;
    padding: 1.5mm 2.5mm;
    border-bottom: 1.5px solid #d97706;
  }
  .l-header-table { width: 100%; border-collapse: collapse; }
  .l-logo-cell { width: 9mm; vertical-align: middle; }
  .l-logo-img { width: 8.5mm; height: 8.5mm; border-radius: 2px; }
  .l-title-cell { vertical-align: middle; padding-left: 2mm; }
  .l-school-name { font-size: 7.2px; font-weight: bold; text-transform: uppercase; color: #ffffff; letter-spacing: 0.3px; line-height: 1.1; }
  .l-school-sub { font-size: 5.2px; color: #fef08a; letter-spacing: 0.2px; line-height: 1.1; margin-top: 0.5px; }
  .l-category-pill { font-size: 5.5px; font-weight: bold; background: rgba(255,255,255,0.22); color: #ffffff; padding: 1px 3.5px; border-radius: 3px; text-transform: uppercase; text-align: right; }

  .l-body {
    height: 34mm;
    padding: 2mm 2.5mm;
    background: #ffffff;
  }
  .l-body-table { width: 100%; border-collapse: collapse; }
  
  .l-photo-cell { width: 22mm; vertical-align: top; text-align: center; }
  .l-photo-wrap {
    width: 20mm;
    height: 24mm;
    border-radius: 3px;
    border: 1.2px solid #8C2826;
    background: #f8fafc;
    overflow: hidden;
    margin: 0 auto;
    text-align: center;
  }
  .l-photo-img { width: 20mm; height: 24mm; object-fit: cover; }
  .l-avatar-initials {
    width: 20mm;
    height: 24mm;
    line-height: 24mm;
    font-size: 11px;
    font-weight: bold;
    color: #ffffff;
    text-align: center;
  }

  .l-info-cell {
    vertical-align: top;
    padding-left: 2.5mm;
  }
  .l-emp-name {
    font-size: 8.5px;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    line-height: 1.15;
    margin-bottom: 1px;
  }
  .l-emp-role {
    font-size: 7px;
    font-weight: bold;
    margin-bottom: 1.5px;
  }

  .l-details-box {
    background: #f8fafc;
    border: 0.5px solid #e2e8f0;
    border-radius: 2.5px;
    padding: 1.5px 3px;
    margin-bottom: 1.5px;
  }
  .l-kv-table { width: 100%; border-collapse: collapse; font-size: 6.2px; line-height: 1.25; }
  .l-kv-lbl { width: 12mm; color: #64748b; font-weight: bold; }
  .l-kv-val { color: #0f172a; font-weight: bold; }

  .l-signature-row {
    margin-top: 1px;
  }
  .l-sig-table { width: 100%; border-collapse: collapse; font-size: 5.5px; }
  .l-sig-img { height: 6.5mm; }

  .l-footer {
    height: 6.5mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1mm 2.5mm;
    font-size: 5.8px;
  }
  .l-footer-table { width: 100%; border-collapse: collapse; }
  .l-footer-left { color: #cbd5e1; }
  .l-footer-right { text-align: right; color: #fde047; font-weight: bold; }

  /* Backside Landscape */
  .l-back-header {
    height: 7mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1.5mm 2.5mm;
    border-bottom: 1.5px solid #8C2826;
  }
  .l-back-title { font-size: 6.2px; font-weight: bold; text-transform: uppercase; color: #ffffff; }
  .l-back-side-tag { font-size: 5.5px; font-weight: bold; color: #fde047; text-align: right; text-transform: uppercase; }

  .l-back-body {
    height: 40mm;
    padding: 2mm 2.5mm;
    background: #ffffff;
  }
  .l-back-table { width: 100%; border-collapse: collapse; }
  .l-back-left { vertical-align: top; width: 58mm; padding-right: 2mm; }
  .l-back-right { vertical-align: top; width: 22mm; text-align: center; }

  .l-back-box {
    background: #f8fafc;
    border: 0.5px solid #e2e8f0;
    border-radius: 2.5px;
    padding: 1.5px 3px;
    margin-bottom: 1.5mm;
    font-size: 6.2px;
  }
  .l-back-lbl { font-size: 5.5px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5px; }
  .l-back-val { font-size: 6.2px; font-weight: bold; color: #0f172a; line-height: 1.2; }
  
  .blood-badge {
    display: inline-block;
    background: #dc2626;
    color: #ffffff;
    font-weight: bold;
    padding: 0.5px 3px;
    border-radius: 2px;
    font-size: 6.5px;
  }

  .qr-box {
    width: 20mm;
    height: 20mm;
    border: 0.8px solid #cbd5e1;
    border-radius: 2px;
    background: #ffffff;
    padding: 1mm;
    margin: 0 auto 1mm auto;
    text-align: center;
  }
  .qr-box img { width: 18mm; height: 18mm; }
  .qr-caption { font-size: 5px; color: #64748b; font-weight: bold; }

  .return-note {
    font-size: 5.2px;
    color: #475569;
    line-height: 1.2;
    background: #f1f5f9;
    padding: 1.5px 3px;
    border-radius: 2px;
    border-left: 2px solid #8C2826;
  }

  /* ═════════════════════════════════════════════════════════════════
     PORTRAIT CARD STYLING (54mm x 85.6mm)
  ══════════════════════════════════════════════════════════════════ */
  .card-portrait {
    width: 54mm;
    height: 85.6mm;
    border-radius: 4.5mm;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    page-break-inside: avoid;
    position: relative;
    text-align: center;
    margin: 0 auto;
  }

  .p-header {
    height: 16mm;
    color: #ffffff;
    padding: 1.5mm 2mm;
    border-bottom: 1.5px solid #d97706;
    text-align: center;
  }
  .p-logo-img { width: 7.5mm; height: 7.5mm; border-radius: 2px; margin-bottom: 0.5mm; }
  .p-school-name { font-size: 6.8px; font-weight: bold; text-transform: uppercase; color: #ffffff; letter-spacing: 0.3px; line-height: 1.1; }
  .p-school-sub { font-size: 5px; color: #fef08a; letter-spacing: 0.2px; line-height: 1; margin-top: 0.5px; }

  .p-body {
    height: 61mm;
    padding: 2mm 2.5mm;
    background: #ffffff;
    text-align: center;
  }

  .p-photo-wrap {
    width: 22mm;
    height: 26mm;
    border-radius: 3px;
    background: #f8fafc;
    overflow: hidden;
    margin: 0 auto 1.5mm auto;
    text-align: center;
  }
  .p-photo-img { width: 22mm; height: 26mm; object-fit: cover; }
  .p-avatar-initials {
    width: 22mm;
    height: 26mm;
    line-height: 26mm;
    font-size: 13px;
    font-weight: bold;
    color: #ffffff;
    text-align: center;
  }

  .p-emp-name {
    font-size: 8px;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    line-height: 1.15;
    margin-bottom: 1px;
  }
  .p-emp-role {
    font-size: 6.8px;
    font-weight: bold;
    margin-bottom: 2mm;
  }

  .p-details-box {
    background: #f8fafc;
    border: 0.5px solid #e2e8f0;
    border-radius: 2.5px;
    padding: 1.5px 2.5mm;
    text-align: left;
    margin-bottom: 1.5mm;
  }
  .p-kv-table { width: 100%; border-collapse: collapse; font-size: 6.2px; line-height: 1.3; }
  .p-kv-lbl { width: 14mm; color: #64748b; font-weight: bold; }
  .p-kv-val { color: #0f172a; font-weight: bold; }

  .p-signature-wrap {
    text-align: center;
    margin-top: 1mm;
  }
  .p-sig-img { height: 6mm; }
  .p-sig-text { font-size: 5.2px; color: #334155; font-weight: bold; }

  .p-footer {
    height: 7mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1.2mm 2mm;
    font-size: 5.5px;
    text-align: center;
  }
  .p-footer-table { width: 100%; border-collapse: collapse; }

  /* Backside Portrait */
  .p-back-header {
    height: 9mm;
    background: #1e293b;
    color: #ffffff;
    padding: 1.5mm 2mm;
    border-bottom: 1.5px solid #8C2826;
    text-align: center;
  }
  .p-back-title { font-size: 6.5px; font-weight: bold; text-transform: uppercase; color: #ffffff; }
  .p-back-sub { font-size: 5px; color: #fde047; }

  .p-back-body {
    height: 68mm;
    padding: 2.5mm 2.5mm;
    background: #ffffff;
    text-align: left;
  }
  .p-back-box {
    background: #f8fafc;
    border: 0.5px solid #e2e8f0;
    border-radius: 2.5px;
    padding: 1.5px 2.5mm;
    margin-bottom: 1.5mm;
  }
  .p-back-lbl { font-size: 5.5px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5px; }
  .p-back-val { font-size: 6.2px; font-weight: bold; color: #0f172a; line-height: 1.2; }

  .p-qr-wrap {
    text-align: center;
    margin: 2mm 0;
  }
  .p-qr-wrap img { width: 17mm; height: 17mm; border: 0.5px solid #cbd5e1; padding: 0.5mm; background: #fff; }

  .page-break { page-break-after: always; }
</style>
</head>
<body>

@php
  $schoolLogo = file_exists(public_path('images/school-logo.png')) ? public_path('images/school-logo.png') : null;
  $sigSvgPath = public_path('images/principal-signature.svg');
  $sigDataUri = file_exists($sigSvgPath) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($sigSvgPath)) : null;
@endphp

<div class="print-page">

{{-- ═════════════════════════════════════════════════════════════════
     LANDSCAPE MODE (85.6mm x 54mm)
══════════════════════════════════════════════════════════════════ --}}
@if(($style ?? 'landscape') === 'landscape')

  @if(($side ?? 'both') === 'both')
    {{-- BOTH SIDES: Front on Left, Back on Right (1 staff per row) --}}
    <table class="card-table">
      @foreach($employees as $index => $emp)
      @php
        $theme = $emp->theme_meta ?? [
          'header_color' => '#8C2826',
          'accent_color' => '#8C2826',
          'theme_label'  => 'STAFF',
          'icon'         => '👤',
        ];
        $headerBg = $theme['header_color'] ?? '#8C2826';
        $accentColor = $theme['accent_color'] ?? '#8C2826';
        $themeLabel = $theme['theme_label'] ?? 'STAFF';

        $phone = $emp->emergency_contact_mobile ?: ($emp->mobile ?: '9876543210');
        $fullAddress = $emp->residential_address ?: ($emp->permanent_address ?: ($emp->address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051')));
        $qrSrc = !empty($emp->_qrCodeSvg) ? 'data:image/svg+xml;base64,' . base64_encode($emp->_qrCodeSvg) : null;
      @endphp
      <tr>
        {{-- FRONT SIDE LANDSCAPE --}}
        <td class="card-cell" style="width: 50%;">
          <div class="card-landscape">
            <div class="l-header" style="background: {{ $headerBg }};">
              <table class="l-header-table">
                <tr>
                  @if($schoolLogo)
                  <td class="l-logo-cell">
                    <img src="{{ $schoolLogo }}" class="l-logo-img" alt="">
                  </td>
                  @endif
                  <td class="l-title-cell">
                    <div class="l-school-name">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
                    <div class="l-school-sub">CBSE AFFILIATED SENIOR SECONDARY &bull; ERODE</div>
                  </td>
                  <td style="text-align: right; vertical-align: middle;">
                    <span class="l-category-pill">{{ $themeLabel }} ID</span>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-body">
              <table class="l-body-table">
                <tr>
                  <td class="l-photo-cell">
                    <div class="l-photo-wrap" style="border-color: {{ $accentColor }};">
                      @if($emp->photo && file_exists(public_path('storage/' . $emp->photo)))
                        <img src="{{ public_path('storage/' . $emp->photo) }}" class="l-photo-img" alt="">
                      @else
                        <div class="l-avatar-initials" style="background: {{ $accentColor }};">
                          {{ strtoupper(substr($emp->first_name ?? 'S', 0, 1) . substr($emp->last_name ?? 'T', 0, 1)) }}
                        </div>
                      @endif
                    </div>
                  </td>
                  <td class="l-info-cell">
                    <div class="l-emp-name">{{ $emp->full_name }}</div>
                    <div class="l-emp-role" style="color: {{ $accentColor }};">
                      {{ $emp->designation ?? $themeLabel }} &bull; {{ $emp->department?->name ?? 'Campus' }}
                    </div>

                    <div class="l-details-box">
                      <table class="l-kv-table">
                        <tr>
                          <td class="l-kv-lbl">Emp No</td>
                          <td class="l-kv-val">: {{ $emp->employee_code ?? 'EMP-' . $emp->id }}</td>
                        </tr>
                        <tr>
                          <td class="l-kv-lbl">DOB</td>
                          <td class="l-kv-val">: {{ $emp->dob ? $emp->dob->format('d/m/Y') : '15/08/1990' }}</td>
                        </tr>
                        <tr>
                          <td class="l-kv-lbl">Joined</td>
                          <td class="l-kv-val">: {{ $emp->joining_date ? $emp->joining_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                      </table>
                    </div>

                    <div class="l-signature-row">
                      <table class="l-sig-table">
                        <tr>
                          <td style="vertical-align: middle; color: #64748b;">
                            Valid: <strong style="color: #0f172a;">{{ $validUntil ?? '2026-2027' }}</strong>
                          </td>
                          <td style="text-align: right; vertical-align: bottom;">
                            @if($sigDataUri)
                              <img src="{{ $sigDataUri }}" class="l-sig-img" alt="Principal Sign">
                            @else
                              <span style="font-weight: bold; border-top: 0.5px solid #0f172a; padding-top: 1px;">Principal Signature</span>
                            @endif
                          </td>
                        </tr>
                      </table>
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-footer">
              <table class="l-footer-table">
                <tr>
                  <td class="l-footer-left">{{ $school->website ?? 'www.erodepublicschool.edu.in' }}</td>
                  <td class="l-footer-right">FRONT SIDE</td>
                </tr>
              </table>
            </div>
          </div>
        </td>

        {{-- BACK SIDE LANDSCAPE --}}
        <td class="card-cell" style="width: 50%;">
          <div class="card-landscape">
            <div class="l-back-header">
              <table style="width: 100%;">
                <tr>
                  <td class="l-back-title">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }} — STAFF AUTHORIZATION</td>
                  <td class="l-back-side-tag">BACK SIDE</td>
                </tr>
              </table>
            </div>

            <div class="l-back-body">
              <table class="l-back-table">
                <tr>
                  <td class="l-back-left">
                    <div class="l-back-box">
                      <div class="l-back-lbl">RESIDENTIAL ADDRESS</div>
                      <div class="l-back-val">{{ Str::limit($fullAddress, 75) }}</div>
                    </div>

                    <div class="l-back-box">
                      <table style="width: 100%;">
                        <tr>
                          <td style="width: 50%;">
                            <div class="l-back-lbl">EMERGENCY PHONE</div>
                            <div class="l-back-val">+91 {{ $phone }}</div>
                          </td>
                          <td style="width: 50%;">
                            <div class="l-back-lbl">BLOOD GROUP</div>
                            <div class="l-back-val">
                              <span class="blood-badge">{{ $emp->blood_group ?: 'B+' }}</span>
                            </div>
                          </td>
                        </tr>
                      </table>
                    </div>

                    <div class="return-note">
                      Authorized staff card of <strong>{{ $school->school_name ?? 'Erode Public School' }}</strong>.
                      If found, return to School HR Office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                    </div>
                  </td>

                  <td class="l-back-right">
                    <div class="qr-box">
                      @if($qrSrc)
                        <img src="{{ $qrSrc }}" alt="QR">
                      @else
                        <div style="font-size: 6px; padding-top: 5mm; color: #94a3b8;">[QR CODE]</div>
                      @endif
                      <div class="qr-caption">STAFF VERIFY</div>
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-footer">
              <table class="l-footer-table">
                <tr>
                  <td class="l-footer-left">Staff Helpline: +91 {{ $school->phone ?? '9876543210' }}</td>
                  <td class="l-footer-right">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                </tr>
              </table>
            </div>
          </div>
        </td>
      </tr>

      {{-- Page break after every 3 pairs (6 cards) --}}
      @if(($index + 1) % 3 === 0 && ($index + 1) < $employees->count())
      </table>
      <div class="page-break"></div>
      <table class="card-table">
      @endif

      @endforeach
    </table>

  @elseif(($side ?? 'both') === 'front')
    {{-- FRONT SIDE ONLY (2 per row, 3 rows = 6 per page) --}}
    <table class="card-table">
      @foreach($employees->chunk(2) as $rowIndex => $row)
      <tr>
        @foreach($row as $emp)
        @php
          $theme = $emp->theme_meta ?? [
            'header_color' => '#8C2826',
            'accent_color' => '#8C2826',
            'theme_label'  => 'STAFF',
          ];
          $headerBg = $theme['header_color'] ?? '#8C2826';
          $accentColor = $theme['accent_color'] ?? '#8C2826';
          $themeLabel = $theme['theme_label'] ?? 'STAFF';
        @endphp
        <td class="card-cell" style="width: 50%;">
          <div class="card-landscape">
            <div class="l-header" style="background: {{ $headerBg }};">
              <table class="l-header-table">
                <tr>
                  @if($schoolLogo)
                  <td class="l-logo-cell">
                    <img src="{{ $schoolLogo }}" class="l-logo-img" alt="">
                  </td>
                  @endif
                  <td class="l-title-cell">
                    <div class="l-school-name">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
                    <div class="l-school-sub">CBSE AFFILIATED SENIOR SECONDARY &bull; ERODE</div>
                  </td>
                  <td style="text-align: right; vertical-align: middle;">
                    <span class="l-category-pill">{{ $themeLabel }} ID</span>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-body">
              <table class="l-body-table">
                <tr>
                  <td class="l-photo-cell">
                    <div class="l-photo-wrap" style="border-color: {{ $accentColor }};">
                      @if($emp->photo && file_exists(public_path('storage/' . $emp->photo)))
                        <img src="{{ public_path('storage/' . $emp->photo) }}" class="l-photo-img" alt="">
                      @else
                        <div class="l-avatar-initials" style="background: {{ $accentColor }};">
                          {{ strtoupper(substr($emp->first_name ?? 'S', 0, 1) . substr($emp->last_name ?? 'T', 0, 1)) }}
                        </div>
                      @endif
                    </div>
                  </td>
                  <td class="l-info-cell">
                    <div class="l-emp-name">{{ $emp->full_name }}</div>
                    <div class="l-emp-role" style="color: {{ $accentColor }};">
                      {{ $emp->designation ?? $themeLabel }} &bull; {{ $emp->department?->name ?? 'Campus' }}
                    </div>

                    <div class="l-details-box">
                      <table class="l-kv-table">
                        <tr>
                          <td class="l-kv-lbl">Emp No</td>
                          <td class="l-kv-val">: {{ $emp->employee_code ?? 'EMP-' . $emp->id }}</td>
                        </tr>
                        <tr>
                          <td class="l-kv-lbl">DOB</td>
                          <td class="l-kv-val">: {{ $emp->dob ? $emp->dob->format('d/m/Y') : '15/08/1990' }}</td>
                        </tr>
                        <tr>
                          <td class="l-kv-lbl">Joined</td>
                          <td class="l-kv-val">: {{ $emp->joining_date ? $emp->joining_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                      </table>
                    </div>

                    <div class="l-signature-row">
                      <table class="l-sig-table">
                        <tr>
                          <td style="vertical-align: middle; color: #64748b;">
                            Valid: <strong style="color: #0f172a;">{{ $validUntil ?? '2026-2027' }}</strong>
                          </td>
                          <td style="text-align: right; vertical-align: bottom;">
                            @if($sigDataUri)
                              <img src="{{ $sigDataUri }}" class="l-sig-img" alt="Principal Sign">
                            @else
                              <span style="font-weight: bold; border-top: 0.5px solid #0f172a; padding-top: 1px;">Principal Signature</span>
                            @endif
                          </td>
                        </tr>
                      </table>
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-footer">
              <table class="l-footer-table">
                <tr>
                  <td class="l-footer-left">{{ $school->website ?? 'www.erodepublicschool.edu.in' }}</td>
                  <td class="l-footer-right">FRONT SIDE</td>
                </tr>
              </table>
            </div>
          </div>
        </td>
        @endforeach
        @if($row->count() === 1)
          <td class="card-cell" style="width: 50%;"></td>
        @endif
      </tr>

      @if(($rowIndex + 1) % 3 === 0 && ($rowIndex + 1) < $employees->chunk(2)->count())
      </table>
      <div class="page-break"></div>
      <table class="card-table">
      @endif

      @endforeach
    </table>

  @else
    {{-- BACK SIDE ONLY (2 per row, 3 rows = 6 per page) --}}
    <table class="card-table">
      @foreach($employees->chunk(2) as $rowIndex => $row)
      <tr>
        @foreach($row as $emp)
        @php
          $phone = $emp->emergency_contact_mobile ?: ($emp->mobile ?: '9876543210');
          $fullAddress = $emp->residential_address ?: ($emp->permanent_address ?: ($emp->address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051')));
          $qrSrc = !empty($emp->_qrCodeSvg) ? 'data:image/svg+xml;base64,' . base64_encode($emp->_qrCodeSvg) : null;
        @endphp
        <td class="card-cell" style="width: 50%;">
          <div class="card-landscape">
            <div class="l-back-header">
              <table style="width: 100%;">
                <tr>
                  <td class="l-back-title">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }} — STAFF AUTHORIZATION</td>
                  <td class="l-back-side-tag">BACK SIDE</td>
                </tr>
              </table>
            </div>

            <div class="l-back-body">
              <table class="l-back-table">
                <tr>
                  <td class="l-back-left">
                    <div class="l-back-box">
                      <div class="l-back-lbl">RESIDENTIAL ADDRESS</div>
                      <div class="l-back-val">{{ Str::limit($fullAddress, 75) }}</div>
                    </div>

                    <div class="l-back-box">
                      <table style="width: 100%;">
                        <tr>
                          <td style="width: 50%;">
                            <div class="l-back-lbl">EMERGENCY PHONE</div>
                            <div class="l-back-val">+91 {{ $phone }}</div>
                          </td>
                          <td style="width: 50%;">
                            <div class="l-back-lbl">BLOOD GROUP</div>
                            <div class="l-back-val">
                              <span class="blood-badge">{{ $emp->blood_group ?: 'B+' }}</span>
                            </div>
                          </td>
                        </tr>
                      </table>
                    </div>

                    <div class="return-note">
                      Authorized staff card of <strong>{{ $school->school_name ?? 'Erode Public School' }}</strong>.
                      If found, return to School HR Office. Helpline: +91 {{ $school->phone ?? '9876543210' }}
                    </div>
                  </td>

                  <td class="l-back-right">
                    <div class="qr-box">
                      @if($qrSrc)
                        <img src="{{ $qrSrc }}" alt="QR">
                      @else
                        <div style="font-size: 6px; padding-top: 5mm; color: #94a3b8;">[QR CODE]</div>
                      @endif
                      <div class="qr-caption">STAFF VERIFY</div>
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <div class="l-footer">
              <table class="l-footer-table">
                <tr>
                  <td class="l-footer-left">Staff Helpline: +91 {{ $school->phone ?? '9876543210' }}</td>
                  <td class="l-footer-right">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                </tr>
              </table>
            </div>
          </div>
        </td>
        @endforeach
        @if($row->count() === 1)
          <td class="card-cell" style="width: 50%;"></td>
        @endif
      </tr>

      @if(($rowIndex + 1) % 3 === 0 && ($rowIndex + 1) < $employees->chunk(2)->count())
      </table>
      <div class="page-break"></div>
      <table class="card-table">
      @endif

      @endforeach
    </table>
  @endif

{{-- ═════════════════════════════════════════════════════════════════
     PORTRAIT MODE (54mm x 85.6mm)
══════════════════════════════════════════════════════════════════ --}}
@else

  @if(($side ?? 'both') === 'both')
    {{-- BOTH SIDES PORTRAIT: Front on Left, Back on Right (1 staff per row) --}}
    <table class="card-table">
      @foreach($employees as $index => $emp)
      @php
        $theme = $emp->theme_meta ?? [
          'header_color' => '#8C2826',
          'accent_color' => '#8C2826',
          'theme_label'  => 'STAFF',
        ];
        $headerBg = $theme['header_color'] ?? '#8C2826';
        $accentColor = $theme['accent_color'] ?? '#8C2826';
        $themeLabel = $theme['theme_label'] ?? 'STAFF';

        $phone = $emp->emergency_contact_mobile ?: ($emp->mobile ?: '9876543210');
        $fullAddress = $emp->residential_address ?: ($emp->permanent_address ?: ($emp->address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051')));
        $qrSrc = !empty($emp->_qrCodeSvg) ? 'data:image/svg+xml;base64,' . base64_encode($emp->_qrCodeSvg) : null;
      @endphp
      <tr>
        {{-- FRONT SIDE PORTRAIT --}}
        <td class="card-cell" style="width: 50%;">
          <div class="card-portrait">
            <div class="p-header" style="background: {{ $headerBg }};">
              @if($schoolLogo)
                <img src="{{ $schoolLogo }}" class="p-logo-img" alt="">
              @endif
              <div class="p-school-name">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
              <div class="p-school-sub">{{ $themeLabel }} ID &bull; ERODE</div>
            </div>

            <div class="p-body">
              <div class="p-photo-wrap" style="border: 1.5px solid {{ $accentColor }};">
                @if($emp->photo && file_exists(public_path('storage/' . $emp->photo)))
                  <img src="{{ public_path('storage/' . $emp->photo) }}" class="p-photo-img" alt="">
                @else
                  <div class="p-avatar-initials" style="background: {{ $accentColor }};">
                    {{ strtoupper(substr($emp->first_name ?? 'S', 0, 1) . substr($emp->last_name ?? 'T', 0, 1)) }}
                  </div>
                @endif
              </div>

              <div class="p-emp-name">{{ $emp->full_name }}</div>
              <div class="p-emp-role" style="color: {{ $accentColor }};">
                {{ $emp->designation ?? $themeLabel }} &bull; {{ $emp->department?->name ?? 'Staff' }}
              </div>

              <div class="p-details-box">
                <table class="p-kv-table">
                  <tr>
                    <td class="p-kv-lbl">Emp No</td>
                    <td class="p-kv-val">: {{ $emp->employee_code ?? 'EMP-' . $emp->id }}</td>
                  </tr>
                  <tr>
                    <td class="p-kv-lbl">DOB</td>
                    <td class="p-kv-val">: {{ $emp->dob ? $emp->dob->format('d/m/Y') : '15/08/1990' }}</td>
                  </tr>
                  <tr>
                    <td class="p-kv-lbl">Joined</td>
                    <td class="p-kv-val">: {{ $emp->joining_date ? $emp->joining_date->format('d/m/Y') : '—' }}</td>
                  </tr>
                </table>
              </div>

              <div class="p-signature-wrap">
                @if($sigDataUri)
                  <img src="{{ $sigDataUri }}" class="p-sig-img" alt="Principal Sign">
                @else
                  <div style="height: 5mm; border-bottom: 0.5px solid #0f172a; width: 30mm; margin: 0 auto;"></div>
                @endif
                <div class="p-sig-text">Principal Signature</div>
              </div>
            </div>

            <div class="p-footer">
              <table class="p-footer-table">
                <tr>
                  <td style="text-align: left; color: #cbd5e1;">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                  <td style="text-align: right; color: #fde047; font-weight: bold;">FRONT</td>
                </tr>
              </table>
            </div>
          </div>
        </td>

        {{-- BACK SIDE PORTRAIT --}}
        <td class="card-cell" style="width: 50%;">
          <div class="card-portrait">
            <div class="p-back-header">
              <div class="p-back-title">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
              <div class="p-back-sub">STAFF IDENTITY & EMERGENCY</div>
            </div>

            <div class="p-back-body">
              <div class="p-back-box">
                <div class="p-back-lbl">RESIDENTIAL ADDRESS</div>
                <div class="p-back-val">{{ Str::limit($fullAddress, 70) }}</div>
              </div>

              <div class="p-back-box">
                <div class="p-back-lbl">EMERGENCY PHONE NUMBER</div>
                <div class="p-back-val">+91 {{ $phone }}</div>
              </div>

              <div class="p-back-box">
                <div class="p-back-lbl">BLOOD GROUP</div>
                <div class="p-back-val">
                  <span class="blood-badge">{{ $emp->blood_group ?: 'B+' }}</span>
                </div>
              </div>

              <div class="p-qr-wrap">
                @if($qrSrc)
                  <img src="{{ $qrSrc }}" alt="QR Code">
                @endif
                <div style="font-size: 5px; color: #64748b; font-weight: bold; margin-top: 1px;">STAFF VERIFY</div>
              </div>

              <div class="return-note" style="margin-top: 1mm;">
                If found, return to School HR Office.<br>
                Helpline: +91 {{ $school->phone ?? '9876543210' }}
              </div>
            </div>

            <div class="p-footer">
              <table class="p-footer-table">
                <tr>
                  <td style="text-align: left; color: #cbd5e1;">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                  <td style="text-align: right; color: #fde047; font-weight: bold;">BACK</td>
                </tr>
              </table>
            </div>
          </div>
        </td>
      </tr>

      {{-- Page break after every 3 pairs --}}
      @if(($index + 1) % 3 === 0 && ($index + 1) < $employees->count())
      </table>
      <div class="page-break"></div>
      <table class="card-table">
      @endif

      @endforeach
    </table>

  @else
    {{-- SINGLE SIDE PORTRAIT (3 cards per row, 3 rows = 9 per page) --}}
    <table class="card-table">
      @foreach($employees->chunk(3) as $rowIndex => $row)
      <tr>
        @foreach($row as $emp)
        @php
          $theme = $emp->theme_meta ?? [
            'header_color' => '#8C2826',
            'accent_color' => '#8C2826',
            'theme_label'  => 'STAFF',
          ];
          $headerBg = $theme['header_color'] ?? '#8C2826';
          $accentColor = $theme['accent_color'] ?? '#8C2826';
          $themeLabel = $theme['theme_label'] ?? 'STAFF';

          $phone = $emp->emergency_contact_mobile ?: ($emp->mobile ?: '9876543210');
          $fullAddress = $emp->residential_address ?: ($emp->permanent_address ?: ($emp->address ?: ($school->address ?? 'Chennimalai Road, Erode - 638051')));
          $qrSrc = !empty($emp->_qrCodeSvg) ? 'data:image/svg+xml;base64,' . base64_encode($emp->_qrCodeSvg) : null;
        @endphp
        <td class="card-cell" style="width: 33.33%;">
          @if(($side ?? 'both') === 'front')
          <div class="card-portrait">
            <div class="p-header" style="background: {{ $headerBg }};">
              @if($schoolLogo)
                <img src="{{ $schoolLogo }}" class="p-logo-img" alt="">
              @endif
              <div class="p-school-name">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
              <div class="p-school-sub">{{ $themeLabel }} ID &bull; ERODE</div>
            </div>

            <div class="p-body">
              <div class="p-photo-wrap" style="border: 1.5px solid {{ $accentColor }};">
                @if($emp->photo && file_exists(public_path('storage/' . $emp->photo)))
                  <img src="{{ public_path('storage/' . $emp->photo) }}" class="p-photo-img" alt="">
                @else
                  <div class="p-avatar-initials" style="background: {{ $accentColor }};">
                    {{ strtoupper(substr($emp->first_name ?? 'S', 0, 1) . substr($emp->last_name ?? 'T', 0, 1)) }}
                  </div>
                @endif
              </div>

              <div class="p-emp-name">{{ $emp->full_name }}</div>
              <div class="p-emp-role" style="color: {{ $accentColor }};">
                {{ $emp->designation ?? $themeLabel }} &bull; {{ $emp->department?->name ?? 'Staff' }}
              </div>

              <div class="p-details-box">
                <table class="p-kv-table">
                  <tr>
                    <td class="p-kv-lbl">Emp No</td>
                    <td class="p-kv-val">: {{ $emp->employee_code ?? 'EMP-' . $emp->id }}</td>
                  </tr>
                  <tr>
                    <td class="p-kv-lbl">DOB</td>
                    <td class="p-kv-val">: {{ $emp->dob ? $emp->dob->format('d/m/Y') : '15/08/1990' }}</td>
                  </tr>
                  <tr>
                    <td class="p-kv-lbl">Joined</td>
                    <td class="p-kv-val">: {{ $emp->joining_date ? $emp->joining_date->format('d/m/Y') : '—' }}</td>
                  </tr>
                </table>
              </div>

              <div class="p-signature-wrap">
                @if($sigDataUri)
                  <img src="{{ $sigDataUri }}" class="p-sig-img" alt="Principal Sign">
                @else
                  <div style="height: 5mm; border-bottom: 0.5px solid #0f172a; width: 30mm; margin: 0 auto;"></div>
                @endif
                <div class="p-sig-text">Principal Signature</div>
              </div>
            </div>

            <div class="p-footer">
              <table class="p-footer-table">
                <tr>
                  <td style="text-align: left; color: #cbd5e1;">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                  <td style="text-align: right; color: #fde047; font-weight: bold;">FRONT</td>
                </tr>
              </table>
            </div>
          </div>
          @else
          {{-- BACK SIDE PORTRAIT ONLY --}}
          <div class="card-portrait">
            <div class="p-back-header">
              <div class="p-back-title">{{ $school->school_name ?? 'ERODE PUBLIC SCHOOL' }}</div>
              <div class="p-back-sub">STAFF IDENTITY & EMERGENCY</div>
            </div>

            <div class="p-back-body">
              <div class="p-back-box">
                <div class="p-back-lbl">RESIDENTIAL ADDRESS</div>
                <div class="p-back-val">{{ Str::limit($fullAddress, 70) }}</div>
              </div>

              <div class="p-back-box">
                <div class="p-back-lbl">EMERGENCY PHONE NUMBER</div>
                <div class="p-back-val">+91 {{ $phone }}</div>
              </div>

              <div class="p-back-box">
                <div class="p-back-lbl">BLOOD GROUP</div>
                <div class="p-back-val">
                  <span class="blood-badge">{{ $emp->blood_group ?: 'B+' }}</span>
                </div>
              </div>

              <div class="p-qr-wrap">
                @if($qrSrc)
                  <img src="{{ $qrSrc }}" alt="QR Code">
                @endif
                <div style="font-size: 5px; color: #64748b; font-weight: bold; margin-top: 1px;">STAFF VERIFY</div>
              </div>

              <div class="return-note" style="margin-top: 1mm;">
                If found, return to School HR Office.<br>
                Helpline: +91 {{ $school->phone ?? '9876543210' }}
              </div>
            </div>

            <div class="p-footer">
              <table class="p-footer-table">
                <tr>
                  <td style="text-align: left; color: #cbd5e1;">VALID: {{ $validUntil ?? '2026-2027' }}</td>
                  <td style="text-align: right; color: #fde047; font-weight: bold;">BACK</td>
                </tr>
              </table>
            </div>
          </div>
          @endif
        </td>
        @endforeach
        @for($i = 0; $i < (3 - $row->count()); $i++)
          <td class="card-cell" style="width: 33.33%;"></td>
        @endfor
      </tr>

      @if(($rowIndex + 1) % 3 === 0 && ($rowIndex + 1) < $employees->chunk(3)->count())
      </table>
      <div class="page-break"></div>
      <table class="card-table">
      @endif

      @endforeach
    </table>
  @endif

@endif

</div>
</body>
</html>
