<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Transfer Certificate - {{ $tcData['full_name'] }}</title>
<style>
  @page {
    size: A4 portrait;
    margin: 8mm 9mm 7mm 9mm;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'DejaVu Serif', 'Georgia', 'Times New Roman', serif;
    font-size: 9.8px;
    color: #0f172a;
    background: #fff;
    line-height: 1.32;
  }

  /* Classical Double Certificate Frame */
  .cert-border {
    border: 3px solid #0f172a;
    padding: 3.5px;
    background: #fff;
  }
  .cert-inner {
    border: 1px solid #0f172a;
    padding: 14px 16px 12px 16px;
    position: relative;
    background: #fff;
  }

  /* Watermark */
  .watermark {
    position: absolute;
    top: 30%;
    left: 20%;
    width: 60%;
    opacity: 0.045;
    z-index: 0;
    text-align: center;
  }
  .watermark img {
    width: 320px;
    height: auto;
  }

  .content-layer {
    position: relative;
    z-index: 1;
  }

  /* Header Section */
  .hdr-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 7px;
    border-bottom: 2px solid #0f172a;
    padding-bottom: 7px;
  }
  .hdr-logo {
    width: 72px;
    vertical-align: middle;
    text-align: center;
  }
  .hdr-logo img {
    max-width: 66px;
    max-height: 66px;
  }
  .hdr-center {
    text-align: center;
    vertical-align: middle;
    padding: 0 8px;
  }
  .hdr-trust {
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #7f1d1d;
    text-transform: uppercase;
    font-family: 'DejaVu Sans', sans-serif;
  }
  .hdr-school {
    font-size: 23px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin: 2px 0 3px 0;
    font-family: 'DejaVu Serif', serif;
  }
  .hdr-aff {
    font-size: 9.8px;
    font-weight: 700;
    color: #1e293b;
    font-family: 'DejaVu Sans', sans-serif;
  }
  .hdr-codes {
    font-size: 8.4px;
    color: #475569;
    font-weight: 600;
    font-family: 'DejaVu Sans', sans-serif;
    margin: 2px 0;
  }
  .hdr-addr {
    font-size: 8.2px;
    color: #475569;
    font-family: 'DejaVu Sans', sans-serif;
  }

  /* Meta Details Table */
  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin: 7px 0 8px 0;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 9.5px;
  }
  .meta-table td {
    padding: 2.5px 0;
  }
  .meta-lbl {
    color: #475569;
    font-weight: 600;
  }
  .meta-val {
    color: #0f172a;
    font-weight: 800;
    border-bottom: 1.2px solid #475569;
    padding: 0 6px;
    display: inline-block;
  }

  /* Title Ribbon */
  .title-ribbon {
    text-align: center;
    margin: 6px 0 8px 0;
    background: #0f172a;
    color: #fff;
    padding: 6.5px 0;
  }
  .title-text {
    font-size: 14.5px;
    font-weight: 900;
    letter-spacing: 5px;
    text-transform: uppercase;
    font-family: 'DejaVu Sans', sans-serif;
  }
  .title-sub {
    font-size: 7.8px;
    letter-spacing: 0.5px;
    font-style: italic;
    opacity: 0.92;
    font-family: 'DejaVu Serif', serif;
    margin-top: 2px;
  }

  /* Data Table */
  .tc-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 7px;
  }
  .tc-table td {
    border: 0.75px solid #cbd5e1;
    padding: 6.2px 7.5px;
    vertical-align: middle;
    font-size: 9.5px;
    line-height: 1.3;
  }
  .tc-n {
    width: 25px;
    text-align: center;
    font-weight: 700;
    color: #475569;
    background: #f8fafc;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 8.8px;
  }
  .tc-lbl {
    width: 44%;
    font-weight: 600;
    color: #1e293b;
    background: #fdfdfd;
    font-family: 'DejaVu Sans', sans-serif;
  }
  .tc-val {
    width: 53%;
    font-weight: 700;
    color: #0f172a;
  }
  .tc-highlight {
    color: #0f172a;
    font-weight: 800;
  }

  /* Declaration */
  .cert-decl {
    text-align: center;
    font-style: italic;
    font-size: 8.8px;
    color: #334155;
    margin: 8px 0;
    font-weight: 600;
  }

  /* Signatures Footer */
  .sign-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }
  .sign-table td {
    vertical-align: bottom;
    text-align: center;
    padding: 0 4px;
  }
  .seal-img {
    max-height: 56px;
    max-width: 56px;
    margin: 0 auto;
  }
  .sign-line {
    border-top: 1.2px solid #0f172a;
    padding-top: 4px;
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 8.8px;
    font-weight: 700;
    color: #0f172a;
  }
  .sign-sub {
    font-size: 7.8px;
    color: #64748b;
    font-weight: 600;
    font-family: 'DejaVu Sans', sans-serif;
  }
</style>
</head>
<body>

<div class="cert-border">
<div class="cert-inner">

  @if(!empty($logoBase64))
  <div class="watermark">
    <img src="{{ $logoBase64 }}" alt="Watermark">
  </div>
  @endif

  <div class="content-layer">

    {{-- School Header --}}
    <table class="hdr-table">
      <tr>
        <td class="hdr-logo">
          @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" alt="School Logo">
          @endif
        </td>
        <td class="hdr-center">
          <div class="hdr-trust">Estd. {{ $school?->established_year ?? '1999' }} &bull; {{ $school?->trust_name ?? 'Education Foundation' }}</div>
          <div class="hdr-school">{{ $school?->school_name ?? 'Erode Public School' }}</div>
          <div class="hdr-aff">
            Affiliated to {{ strtoupper($school?->board ?? 'CBSE') }} Board, New Delhi
            @if(!empty($school?->affiliation_number))
              &bull; Affiliation No.: {{ $school->affiliation_number }}
            @endif
          </div>
          <div class="hdr-codes">
            School Code: {{ $school?->school_code ?? 'SCH001' }} &nbsp;|&nbsp;
            U-DISE Code: {{ $school?->udise_code ?? '33100201501' }} &nbsp;|&nbsp;
            State: Tamil Nadu
          </div>
          <div class="hdr-addr">
            {{ $school?->address ?? '123, Main Street' }}, {{ $school?->city ?? 'Erode' }} - {{ $school?->pincode ?? '638001' }}
            @if(!empty($school?->phone)) &bull; Ph: {{ $school->phone }} @endif
            @if(!empty($school?->email)) &bull; Email: {{ $school->email }} @endif
          </div>
        </td>
        <td class="hdr-logo">
          {{-- Empty spacer cell to keep header text symmetrically centered --}}
        </td>
      </tr>
    </table>

    {{-- Meta Details Strip --}}
    <table class="meta-table">
      <tr>
        <td style="width:25%; text-align:left;">
          <span class="meta-lbl">TC No:</span> <span class="meta-val">{{ $tcData['tc_number'] }}</span>
        </td>
        <td style="width:25%; text-align:left;">
          <span class="meta-lbl">Admission No:</span> <span class="meta-val">{{ $tcData['admission_no'] }}</span>
        </td>
        <td style="width:26%; text-align:left;">
          <span class="meta-lbl">EMIS / PEN:</span> <span class="meta-val">{{ $tcData['emis_no'] ?: '—' }}</span>
        </td>
        <td style="width:24%; text-align:right;">
          <span class="meta-lbl">Date of Issue:</span> <span class="meta-val">{{ $tcData['tc_date'] }}</span>
        </td>
      </tr>
    </table>

    {{-- Title Banner --}}
    <div class="title-ribbon">
      <div class="title-text">Transfer Certificate</div>
      <div class="title-sub">(Recognised &amp; Issued in accordance with Board Code and State Educational Rules)</div>
    </div>

    {{-- 24 Serialized Items --}}
    <table class="tc-table">
      <tr>
        <td class="tc-n">1</td>
        <td class="tc-lbl">Name of the Pupil (in Block Letters)</td>
        <td class="tc-val tc-highlight">{{ strtoupper($tcData['full_name']) }}</td>
      </tr>
      <tr>
        <td class="tc-n">2</td>
        <td class="tc-lbl">Father's / Guardian's Name</td>
        <td class="tc-val">{{ $tcData['father_name'] ?: '—' }}</td>
      </tr>
      <tr>
        <td class="tc-n">3</td>
        <td class="tc-lbl">Mother's Name</td>
        <td class="tc-val">{{ $tcData['mother_name'] ?: '—' }}</td>
      </tr>
      <tr>
        <td class="tc-n">4</td>
        <td class="tc-lbl">Nationality</td>
        <td class="tc-val">{{ $tcData['nationality'] ?: 'Indian' }}</td>
      </tr>
      <tr>
        <td class="tc-n">5</td>
        <td class="tc-lbl">Whether candidate belongs to SC / ST / OBC / General</td>
        <td class="tc-val">{{ strtoupper($tcData['category'] ?: 'General') }}</td>
      </tr>
      <tr>
        <td class="tc-n">6</td>
        <td class="tc-lbl">Religion and Community / Caste</td>
        <td class="tc-val">
          {{ $tcData['religion'] ?: '—' }}
          @if(!empty($tcData['caste'])) / {{ $tcData['caste'] }} @endif
        </td>
      </tr>
      <tr>
        <td class="tc-n">7</td>
        <td class="tc-lbl">Date of First Admission in School with Class</td>
        <td class="tc-val">{{ $tcData['admission_date'] ?: '—' }} ({{ str_starts_with(trim($tcData['admitted_class'] ?? ''), 'Class') ? $tcData['admitted_class'] : 'Class ' . ($tcData['admitted_class'] ?: '—') }})</td>
      </tr>
      <tr>
        <td class="tc-n">8</td>
        <td class="tc-lbl">Date of Birth according to Admission Register<br><span style="font-size:7.2px;color:#64748b;font-weight:normal;">(a) In Figures &nbsp;&bull;&nbsp; (b) In Words</span></td>
        <td class="tc-val">
          <strong>{{ $tcData['dob_formatted'] ?: '—' }}</strong><br>
          <span style="font-style:italic;color:#334155;font-size:8.6px;">{{ $tcData['dob_in_words'] ?: '—' }}</span>
        </td>
      </tr>
      <tr>
        <td class="tc-n">9</td>
        <td class="tc-lbl">Place of Birth (District &amp; State)</td>
        <td class="tc-val">{{ $tcData['place_of_birth'] ?: '—' }}</td>
      </tr>
      <tr>
        <td class="tc-n">10</td>
        <td class="tc-lbl">Class in which the pupil last studied</td>
        <td class="tc-val"><strong>{{ str_starts_with(trim($tcData['last_class'] ?? ''), 'Class') ? $tcData['last_class'] : 'Class ' . ($tcData['last_class'] ?: '—') }}</strong></td>
      </tr>
      <tr>
        <td class="tc-n">11</td>
        <td class="tc-lbl">School / Board Annual Examination last taken with result</td>
        <td class="tc-val">{{ $tcData['exam_result'] ?: 'Passed' }}</td>
      </tr>
      <tr>
        <td class="tc-n">12</td>
        <td class="tc-lbl">Whether failed, if so once / twice in same class</td>
        <td class="tc-val">{{ $tcData['whether_failed'] ?: 'No' }}</td>
      </tr>
      <tr>
        <td class="tc-n">13</td>
        <td class="tc-lbl">Subjects Studied</td>
        <td class="tc-val" style="font-size:8.8px;">{{ $tcData['subjects'] ?: '1. English  2. Mathematics  3. Science  4. Social Science  5. Language' }}</td>
      </tr>
      <tr>
        <td class="tc-n">14</td>
        <td class="tc-lbl">Whether qualified for promotion to higher class</td>
        <td class="tc-val tc-highlight">{{ $tcData['qualified_promotion'] ?: 'Yes, Promoted' }}</td>
      </tr>
      <tr>
        <td class="tc-n">15</td>
        <td class="tc-lbl">Month up to which the pupil has paid school dues</td>
        <td class="tc-val">{{ $tcData['dues_cleared'] ?: 'All Dues Paid' }}</td>
      </tr>
      <tr>
        <td class="tc-n">16</td>
        <td class="tc-lbl">Any fee concession availed of (nature of concession)</td>
        <td class="tc-val">{{ $tcData['fee_concession'] ?: 'No' }}</td>
      </tr>
      <tr>
        <td class="tc-n">17</td>
        <td class="tc-lbl">Total number of working days &amp; Attendance</td>
        <td class="tc-val">Working Days: <strong>{{ $tcData['total_working_days'] }}</strong> &nbsp;|&nbsp; Present: <strong>{{ $tcData['days_present'] }} Days</strong></td>
      </tr>
      <tr>
        <td class="tc-n">18</td>
        <td class="tc-lbl">Whether NCC Cadet / Boy Scout / Girl Guide / NSS</td>
        <td class="tc-val">{{ $tcData['ncc_scout'] ?: 'N/A' }}</td>
      </tr>
      <tr>
        <td class="tc-n">19</td>
        <td class="tc-lbl">Games played or extra-curricular activities participated in</td>
        <td class="tc-val">{{ is_array($tcData['games_activities']) ? implode(', ', $tcData['games_activities']) : ($tcData['games_activities'] ?: 'General Physical Education & School Games') }}</td>
      </tr>
      <tr>
        <td class="tc-n">20</td>
        <td class="tc-lbl">General Conduct and Character</td>
        <td class="tc-val tc-highlight">{{ $tcData['conduct'] ?: 'Good' }}</td>
      </tr>
      <tr>
        <td class="tc-n">21</td>
        <td class="tc-lbl">Date of application for certificate</td>
        <td class="tc-val">{{ $tcData['application_date'] ?: '—' }}</td>
      </tr>
      <tr>
        <td class="tc-n">22</td>
        <td class="tc-lbl">Date of issue of certificate / Date of leaving</td>
        <td class="tc-val"><strong>{{ $tcData['leaving_date'] ?: $tcData['tc_date'] }}</strong></td>
      </tr>
      <tr>
        <td class="tc-n">23</td>
        <td class="tc-lbl">Reason for leaving the school</td>
        <td class="tc-val">{{ $tcData['reason'] ?: "Parent's Request" }}</td>
      </tr>
      <tr>
        <td class="tc-n">24</td>
        <td class="tc-lbl">Any other remarks</td>
        <td class="tc-val" style="font-style:italic;">{{ $tcData['remarks'] ?: 'Conduct and Character have been Good during the period of study.' }}</td>
      </tr>
    </table>

    {{-- Certification Declaration --}}
    <div class="cert-decl">
      "Certified that the above particulars are in accordance with the School Admission and Attendance Register."
    </div>

    {{-- Signatures Footer --}}
    <table class="sign-table">
      <tr>
        <td style="width:25%;">
          <div style="height:48px;"></div>
          <div class="sign-line">Prepared by</div>
          <div class="sign-sub">Dealing Assistant</div>
        </td>
        <td style="width:25%;">
          <div style="height:48px;"></div>
          <div class="sign-line">Checked by</div>
          <div class="sign-sub">Class Teacher / Coordinator</div>
        </td>
        <td style="width:25%;">
          @if(!empty($sealBase64))
            <img src="{{ $sealBase64 }}" class="seal-img"><br>
          @else
            <div style="height:48px;"></div>
          @endif
          <div class="sign-line">School Seal</div>
          <div class="sign-sub">Official Stamp</div>
        </td>
        <td style="width:25%;">
          @if(!empty($signatureBase64))
            <img src="{{ $signatureBase64 }}" style="max-height:40px;margin-bottom:2px;"><br>
          @else
            <div style="height:48px;"></div>
          @endif
          <div class="sign-line">{{ $school?->principal_name ?? 'Principal' }}</div>
          <div class="sign-sub">Head of Institution</div>
        </td>
      </tr>
    </table>

  </div>

</div>
</div>

</body>
</html>
