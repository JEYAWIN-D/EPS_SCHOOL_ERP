<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Erode Public School Senior Secondary</title>
  <link rel="icon" type="image/png" href="{{ asset('images/school-seal-badge.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { min-height: 100%; }

    /* Top navigation progress loader */
    #dasa-nav-loader {
      background: linear-gradient(90deg, #8c2826 0%, #c8973a 50%, #a13431 100%) !important;
      box-shadow: 0 0 12px rgba(161, 52, 49, 0.9) !important;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: radial-gradient(circle at 50% 100px, rgba(140, 40, 38, 0.03) 0%, #f8fafc 55%, #f1f5f9 100%);
      color: #0f172a;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      padding: 0;
    }

    /* ── 1. Full-Screen Horizontal Header — White Letterhead Style ── */
    .full-screen-header {
      width: 100%;
      background: #ffffff;
      border-bottom: 3px solid #8c2826;
      padding: 0.85rem 2rem;
      position: relative;
      z-index: 10;
      flex-shrink: 0;
    }
    .header-container {
      width: 100%;
      max-width: 1300px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .header-brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 1.4rem;
      text-decoration: none;
      position: relative;
      z-index: 2;
    }

    /* ── Crest: Borderless, No Plaque Box ── */
    .crest-seal {
      height: 7rem;
      width: auto;
      flex-shrink: 0;
    }
    .crest-seal-img {
      height: 100%;
      width: auto;
      object-fit: contain;
    }

    /* ── Brand Typography: Stacked, Large Maroon Text ── */
    .brand-text-group {
      text-align: left;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0;
    }
    .brand-school-title {
      font-size: 2.65rem;
      font-weight: 900;
      color: #8c2826;
      letter-spacing: 0.01em;
      line-height: 1.05;
      margin: 0;
      font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
      text-transform: uppercase;
    }
    .brand-school-sub {
      font-size: 1.55rem;
      font-weight: 900;
      color: #8c2826;
      letter-spacing: 0.01em;
      line-height: 1.1;
      display: block;
      font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
      text-transform: uppercase;
    }
    .brand-school-cbse {
      font-size: 0.92rem;
      color: #8c2826;
      font-weight: 700;
      line-height: 1.4;
      letter-spacing: 0.01em;
      margin-top: 0.2rem;
    }

    @media (max-width: 900px) {
      .full-screen-header {
        padding: 0.7rem 1.2rem;
      }
      .crest-seal { height: 5.5rem; }
      .brand-school-title { font-size: 2rem; }
      .brand-school-sub { font-size: 1.15rem; }
      .brand-school-cbse { font-size: 0.8rem; }
    }

    @media (max-width: 600px) {
      .crest-seal { height: 4rem; }
      .header-brand { gap: 0.9rem; }
      .brand-school-title { font-size: 1.35rem; }
      .brand-school-sub { font-size: 0.85rem; }
      .brand-school-cbse { font-size: 0.7rem; }
    }

    /* ── 2. Main Login Area ── */
    .login-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2.25rem 1.25rem;
      width: 100%;
    }
    .login-wrapper {
      width: 100%;
      max-width: 440px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.25rem;
    }

    /* ── 3. Clean White Login Card ── */
    .login-card {
      width: 100%;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 1.25rem;
      box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.04),
        0 20px 25px -5px rgba(0, 0, 0, 0.05);
      padding: 2.25rem 2rem;
      position: relative;
    }
    @media (min-width: 480px) {
      .login-card {
        padding: 2.5rem 2.25rem;
      }
    }

    .card-title-group {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .card-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
      line-height: 1.2;
    }
    .card-subtitle {
      font-size: 0.8125rem;
      color: #64748b;
      margin-top: 0.25rem;
      font-weight: 500;
    }

    /* ── Quick Role Switcher Tabs ── */
    .role-bar {
      margin-bottom: 1.45rem;
      background: #f1f5f9;
      border-radius: 0.75rem;
      padding: 0.3rem;
    }
    .role-bar-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.25rem;
    }
    .role-tab {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.2rem;
      padding: 0.5rem 0.2rem;
      border-radius: 0.55rem;
      border: none;
      background: transparent;
      cursor: pointer;
      color: #475569;
      font-size: 0.72rem;
      font-weight: 600;
      font-family: 'Plus Jakarta Sans', sans-serif;
      transition: all 0.15s ease;
      touch-action: manipulation;
    }
    .role-tab:hover {
      color: #a13431;
      background: rgba(255, 255, 255, 0.75);
    }
    .role-tab.active {
      background: #a13431;
      color: #ffffff;
      font-weight: 700;
      box-shadow: 0 2px 6px rgba(161, 52, 49, 0.3);
    }

    .role-bar-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 0.35rem;
      padding: 0.15rem 0.35rem 0;
    }
    .role-bar-hint {
      font-size: 0.6875rem;
      color: #64748b;
    }
    .role-bar-link {
      font-size: 0.72rem;
      font-weight: 700;
      color: #a13431;
      background: none;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.2rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .role-bar-link:hover {
      text-decoration: underline;
    }

    /* Error alert */
    .error-alert {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 0.65rem;
      padding: 0.65rem 0.85rem;
      margin-bottom: 1.25rem;
      color: #b91c1c;
      font-size: 0.8125rem;
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
    }

    /* Form Fields */
    .field-group {
      margin-bottom: 1.25rem;
    }
    .field-label {
      display: block;
      font-size: 0.78rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.4rem;
    }
    .input-box {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-icon {
      position: absolute;
      left: 0.95rem;
      color: #94a3b8;
      pointer-events: none;
      display: flex;
      align-items: center;
    }
    .field-input {
      width: 100%;
      border: 1.5px solid #cbd5e1;
      border-radius: 0.65rem;
      padding: 0.75rem 1rem 0.75rem 2.65rem;
      font-size: 14.5px;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      font-family: 'Inter', sans-serif;
    }
    .field-input:focus {
      border-color: #a13431;
      box-shadow: 0 0 0 3.5px rgba(161, 52, 49, 0.12);
    }
    .field-input:focus ~ .input-icon {
      color: #a13431;
    }
    .field-input::placeholder {
      color: #94a3b8;
    }
    .field-error {
      font-size: 0.75rem;
      color: #dc2626;
      margin-top: 0.35rem;
    }

    .pass-toggle {
      position: absolute;
      right: 0.85rem;
      background: none;
      border: none;
      cursor: pointer;
      color: #94a3b8;
      padding: 0.25rem;
      display: flex;
      align-items: center;
    }
    .pass-toggle:hover {
      color: #334155;
    }

    .meta-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .remember-label {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      font-size: 0.8125rem;
      color: #475569;
      cursor: pointer;
      user-select: none;
      font-weight: 500;
    }
    .remember-cb {
      width: 1rem;
      height: 1rem;
      accent-color: #a13431;
      cursor: pointer;
    }
    .forgot-link {
      font-size: 0.8125rem;
      color: #a13431;
      text-decoration: none;
      font-weight: 600;
    }
    .forgot-link:hover {
      text-decoration: underline;
    }

    /* Submit Button */
    .submit-btn {
      width: 100%;
      padding: 0.85rem 1.25rem;
      background: #a13431;
      color: #ffffff;
      border: none;
      border-radius: 0.65rem;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.01em;
      cursor: pointer;
      transition: all 0.18s ease;
      font-family: 'Plus Jakarta Sans', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      min-height: 48px;
      touch-action: manipulation;
    }
    .submit-btn:hover:not(:disabled) {
      background: #8c2826;
      box-shadow: 0 4px 14px rgba(161, 52, 49, 0.35);
      transform: translateY(-1px);
    }
    .submit-btn:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }

    /* ── Step 2: PIN Verification ── */
    .pin-shield-wrap {
      text-align: center;
      margin-bottom: 1.25rem;
    }
    .pin-shield-badge {
      width: 58px;
      height: 58px;
      border-radius: 50%;
      background: linear-gradient(135deg, #fdf2f2 0%, #fee2e2 100%);
      border: 2px solid #fecaca;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.85rem auto;
      box-shadow: 0 4px 12px rgba(161, 52, 49, 0.12);
    }
    .pin-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
      margin-bottom: 0.2rem;
    }
    .pin-subtitle {
      font-size: 0.8125rem;
      color: #64748b;
      font-weight: 500;
    }
    .pin-user-pill {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 0.65rem;
      padding: 0.55rem 0.85rem;
      margin-bottom: 1.25rem;
    }

    /* ── 3. Simple Centered Footer ── */
    .footer-center {
      text-align: center;
      font-size: 0.78rem;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      flex-wrap: wrap;
      width: 100%;
      line-height: 1.5;
    }
    .footer-dot {
      color: #cbd5e1;
    }
    .footer-powered {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: #64748b;
      font-weight: 500;
    }
    .dasa-text {
      font-weight: 600;
      color: #334155;
    }

    /* ── All 23 Roles Directory Modal ── */
    .demo-overlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 99999;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
      padding: 1rem;
      overflow-y: auto;
    }
    .demo-overlay.show {
      display: flex;
    }

    .demo-modal {
      background: #ffffff;
      border-radius: 1.25rem;
      width: 100%;
      max-width: 720px;
      max-height: calc(100vh - 3rem);
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      animation: modalPop 0.2s ease-out;
      margin: auto;
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: translateY(12px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .demo-modal-head {
      padding: 1.15rem 1.35rem;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .demo-modal-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.05rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .demo-modal-crest {
      width: 1.5rem;
      height: 1.5rem;
      object-fit: contain;
    }
    .demo-modal-sub {
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 0.15rem;
    }
    .demo-close-btn {
      width: 2rem;
      height: 2rem;
      border-radius: 0.5rem;
      border: none;
      background: #e2e8f0;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #334155;
      transition: all 0.15s;
      flex-shrink: 0;
    }
    .demo-close-btn:hover {
      background: #cbd5e1;
      color: #0f172a;
    }

    .demo-modal-body {
      overflow-y: auto;
      padding: 1.15rem 1.35rem;
      flex: 1;
      -webkit-overflow-scrolling: touch;
    }

    .pw-badge-box {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      background: #fdf5f6;
      border: 1px solid #f5ccd0;
      border-radius: 0.65rem;
      padding: 0.5rem 0.85rem;
      font-size: 0.78rem;
      font-weight: 600;
      color: #8c2826;
      margin-bottom: 1.25rem;
      width: 100%;
    }

    .role-group {
      margin-bottom: 1.25rem;
    }
    .role-group-title {
      font-size: 0.6875rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 0.5rem;
      padding-left: 0.15rem;
    }
    .role-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 0.4rem;
    }
    @media (min-width: 480px) { .role-grid { grid-template-columns: 1fr 1fr; } }
    @media (min-width: 620px) { .role-grid { grid-template-columns: 1fr 1fr 1fr; } }

    .role-btn-card {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.55rem 0.7rem;
      border-radius: 0.65rem;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.15s;
      text-align: left;
      touch-action: manipulation;
    }
    .role-btn-card:hover {
      border-color: #a13431;
      background: #fff5f6;
      box-shadow: 0 0 0 3px rgba(161, 52, 49, 0.08);
    }
    .role-btn-card:hover .role-card-name {
      color: #a13431;
    }
    .role-dot {
      width: 0.5rem;
      height: 0.5rem;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .role-card-info {
      min-width: 0;
      flex: 1;
    }
    .role-card-name {
      font-size: 0.78rem;
      font-weight: 700;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .role-card-email {
      font-size: 0.68rem;
      color: #64748b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .demo-modal-foot {
      padding: 0.75rem 1.35rem;
      border-top: 1px solid #e2e8f0;
      background: #f8fafc;
      flex-shrink: 0;
      text-align: center;
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 500;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin 0.7s linear infinite; }
  </style>
</head>
<body>
@include('partials.mobile-nav-speed')

  <!-- 1. Full-Screen Horizontal Institutional Header -->
  <header class="full-screen-header">
    <div class="header-container">
      <a href="{{ url('/') }}" class="header-brand" title="Erode Public School Senior Secondary">
        <div class="crest-seal">
          <img
            src="{{ asset('images/school-logo.png') }}"
            alt="Erode Public School Crest"
            class="crest-seal-img"
          >
        </div>
        <div class="brand-text-group">
          <h1 class="brand-school-title">ERODE PUBLIC SCHOOL</h1>
          <span class="brand-school-sub">SENIOR SECONDARY</span>
          <div class="brand-school-cbse">Affiliated to CBSE, New Delhi &nbsp;&nbsp;Aff No.1931585</div>
        </div>
      </a>
    </div>
  </header>

  <!-- 2. Main Centered Login Section -->
  <main class="login-main">
    <div class="login-wrapper">

      <div class="login-card" x-data="authLoginForm()">
        <!-- Title & Role Switcher: Active on Step 1 (Credentials) -->
        <div x-show="step === 'credentials'">
          <!-- Title -->
          <div class="card-title-group">
            <h2 class="card-title">Portal Sign In</h2>
            <p class="card-subtitle" id="roleSubtitle">Select your role or enter credentials</p>
          </div>

          <!-- Quick Role Switcher -->
          <div class="role-bar">
            <div class="role-bar-grid">
              <!-- Admin -->
              <button type="button" class="role-tab active" id="tab-admin" onclick="switchRole('admin', 'admin@schoolerp.in', 'Admin@1234')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Admin</span>
              </button>

              <!-- Student -->
              <button type="button" class="role-tab" id="tab-student" onclick="switchRole('student', 'student@schoolerp.in', 'Demo@2026')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Student</span>
              </button>

              <!-- Parent -->
              <button type="button" class="role-tab" id="tab-parent" onclick="switchRole('parent', 'parent@schoolerp.in', 'Demo@2026')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Parent</span>
              </button>

              <!-- Teacher -->
              <button type="button" class="role-tab" id="tab-teacher" onclick="switchRole('teacher', 'teacher@schoolerp.in', 'Demo@2026')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Teacher</span>
              </button>
            </div>

            <div class="role-bar-foot">
              <span class="role-bar-hint">1-click demo credentials</span>
              <button type="button" class="role-bar-link" onclick="openDemoModal()">
                <span>All 23 roles directory</span>
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Flash Error Alert -->
        @if($errors->any())
          <div class="error-alert">
            <svg style="width:1.15rem;height:1.15rem;flex-shrink:0;margin-top:0.05rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        <!-- Form -->
        <form id="login-form" method="POST" action="{{ route('login') }}" @submit="handleSubmit($event)">
          @csrf

          <!-- STEP 1: Credentials Section (Email & Password) -->
          <div x-show="step === 'credentials'">
            <!-- Email or Mobile or Employee ID -->
            <div class="field-group">
              <label class="field-label" for="email-field">Email Address, Employee ID, or Mobile</label>
              <div class="input-box">
                <input
                  id="email-field"
                  type="text"
                  name="email"
                  x-model="email"
                  required
                  autofocus
                  class="field-input"
                  placeholder="e.g. EPSS9790, name@schoolerp.in, or mobile"
                >
                <div class="input-icon">
                  <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                </div>
              </div>
              @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <!-- Password -->
            <div class="field-group" x-data="{ show: false }">
              <label class="field-label" for="pass-field">Password</label>
              <div class="input-box">
                <input
                  id="pass-field"
                  :type="show ? 'text' : 'password'"
                  name="password"
                  required
                  class="field-input"
                  style="padding-right: 2.75rem;"
                  placeholder="••••••••"
                >
                <div class="input-icon">
                  <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <button
                  type="button"
                  class="pass-toggle"
                  @click="show = !show"
                  title="Toggle password visibility"
                  aria-label="Toggle password visibility"
                >
                  <svg x-show="!show" style="width:1.15rem;height:1.15rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <svg x-show="show"  style="width:1.15rem;height:1.15rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
              </div>
              <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.35rem; line-height: 1.35;">
                Staff first-time login: Enter your Employee ID (e.g. <span style="font-weight: 600; color: #8c2826;">EPSS9790</span>) and your Date of Birth in DDMMYYYY format (e.g. <span style="font-weight: 600; color: #8c2826;">15091998</span>).
              </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="meta-row">
              <label class="remember-label">
                <input type="checkbox" name="remember" class="remember-cb" checked>
                <span>Keep me signed in</span>
              </label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <!-- Submit Button: "Sign In to ERP Portal" -->
            <button type="submit" class="submit-btn" :disabled="loading">
              <span x-show="!loading" style="display:flex;align-items:center;gap:0.5rem">
                <span>Sign In to ERP Portal</span>
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </span>
              <span x-show="loading" style="display:flex;align-items:center;gap:0.5rem">
                <svg class="spinner" style="width:1.15rem;height:1.15rem" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span>Authenticating…</span>
              </span>
            </button>
          </div>

          <!-- STEP 2: Security PIN Verification (Shown after clicking Sign In for Admin) -->
          <div x-show="step === 'pin'" style="display: none;">
            <!-- Header with Shield Badge -->
            <div class="pin-shield-wrap">
              <div class="pin-shield-badge">
                <svg width="28" height="28" fill="none" stroke="#8c2826" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <h3 class="pin-title">Enter Admin Security PIN</h3>
              <p class="pin-subtitle">Two-step administrative authentication required</p>
            </div>

            <!-- Active User Account Pill -->
            <div class="pin-user-pill">
              <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; flex-shrink: 0;"></div>
                <span style="font-size: 0.8125rem; font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="email || 'admin@schoolerp.in'"></span>
              </div>
              <button type="button" @click="step = 'credentials'" style="font-size: 0.75rem; font-weight: 700; color: #a13431; background: none; border: none; cursor: pointer; text-decoration: underline;">
                Change
              </button>
            </div>

            <!-- PIN Input Field -->
            <div style="margin-bottom: 1.5rem; text-align: center;">
              <label class="field-label" for="pin-field" style="margin-bottom: 0.45rem; text-align: center;">
                Security PIN
              </label>
              <div style="position: relative; max-width: 220px; margin: 0 auto;">
                <input
                  id="pin-field"
                  type="password"
                  inputmode="numeric"
                  maxlength="6"
                  name="admin_pin"
                  x-model="pin"
                  class="field-input"
                  placeholder="••••"
                  autocomplete="one-time-code"
                  style="text-align: center; font-size: 1.6rem; letter-spacing: 0.45em; font-weight: 800; padding: 0.65rem 0.5rem; border-radius: 0.75rem; border: 2px solid #cbd5e1;"
                >
              </div>
              @error('admin_pin')<p class="field-error" style="text-align: center; margin-top: 0.4rem;">{{ $message }}</p>@enderror

              @if(app()->environment('local', 'testing'))
              <div style="margin-top: 0.65rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                <span style="font-size: 0.72rem; color: #64748b;">Dev Default:</span>
                <button type="button" @click="pin = '1122'" style="font-size: 0.72rem; font-weight: 700; color: #8c2826; background: #fdf5f6; border: 1px solid #f5ccd0; border-radius: 0.4rem; padding: 0.15rem 0.45rem; cursor: pointer;">
                  1122 (Click to auto-fill)
                </button>
              </div>
              @endif
            </div>

            <!-- Verify PIN & Access Portal Button -->
            <button type="submit" class="submit-btn" :disabled="loading" style="margin-bottom: 0.75rem;">
              <span x-show="!loading" style="display:flex;align-items:center;gap:0.5rem">
                <span>Verify PIN & Access Portal</span>
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </span>
              <span x-show="loading" style="display:flex;align-items:center;gap:0.5rem">
                <svg class="spinner" style="width:1.15rem;height:1.15rem" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span>Authorizing…</span>
              </span>
            </button>

            <!-- Back to Credentials Button -->
            <button type="button" @click="step = 'credentials'" style="width: 100%; background: none; border: none; font-size: 0.8125rem; font-weight: 600; color: #64748b; padding: 0.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem; font-family: 'Plus Jakarta Sans', sans-serif;">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
              <span>Back to credentials</span>
            </button>
          </div>
        </form>

      </div>

    <!-- 3. Simple Centered Footer -->
    <footer class="footer-center">
      <span>&copy; {{ date('Y') }} Erode Public School</span>
      <span class="footer-dot">&bull;</span>
      <span class="footer-powered">Powered by <span class="dasa-text">DASA Tech</span></span>
    </footer>

  </div>
</main>

  {{-- ── All 23 Roles Directory Modal ── --}}
  <div class="demo-overlay" id="demo-overlay" onclick="closeDemoModal(event)">
    <div class="demo-modal" onclick="event.stopPropagation()">

      <div class="demo-modal-head">
        <div>
          <div class="demo-modal-title">
            <img src="{{ asset('images/school-seal-badge.png') }}" alt="Crest" class="demo-modal-crest" onerror="this.style.display='none'">
            <span>All 23 Role Demo Accounts Directory</span>
          </div>
          <div class="demo-modal-sub">Click any role to immediately load credentials</div>
        </div>
        <button class="demo-close-btn" onclick="closeDemoModal()" title="Close dialog" aria-label="Close">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="demo-modal-body">

        <div class="pw-badge-box">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <div>All role passwords: <strong>Demo@2026</strong> &nbsp;|&nbsp; Super Admin: <strong>Admin@1234</strong></div>
        </div>

        <!-- Management -->
        <div class="role-group">
          <div class="role-group-title">Executive &amp; Leadership</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('admin@schoolerp.in','Admin@1234', 'Super Admin')">
              <div class="role-dot" style="background:#a13431"></div>
              <div class="role-card-info"><div class="role-card-name">Super Admin</div><div class="role-card-email">admin@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('owner@schoolerp.in','Demo@2026', 'Owner')">
              <div class="role-dot" style="background:#dc2626"></div>
              <div class="role-card-info"><div class="role-card-name">Owner</div><div class="role-card-email">owner@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('schooladmin@schoolerp.in','Demo@2026', 'School Admin')">
              <div class="role-dot" style="background:#ea580c"></div>
              <div class="role-card-info"><div class="role-card-name">School Admin</div><div class="role-card-email">schooladmin@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('principal@schoolerp.in','Demo@2026', 'Principal')">
              <div class="role-dot" style="background:#c8973a"></div>
              <div class="role-card-info"><div class="role-card-name">Principal</div><div class="role-card-email">principal@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('viceprincipal@schoolerp.in','Demo@2026', 'Vice Principal')">
              <div class="role-dot" style="background:#4f46e5"></div>
              <div class="role-card-info"><div class="role-card-name">Vice Principal</div><div class="role-card-email">viceprincipal@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('hod@schoolerp.in','Demo@2026', 'HOD')">
              <div class="role-dot" style="background:#7c3aed"></div>
              <div class="role-card-info"><div class="role-card-name">HOD</div><div class="role-card-email">hod@schoolerp.in</div></div>
            </button>
          </div>
        </div>

        <!-- Teaching -->
        <div class="role-group">
          <div class="role-group-title">Faculty &amp; Teaching Staff</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('classteacher@schoolerp.in','Demo@2026', 'Class Teacher')">
              <div class="role-dot" style="background:#0891b2"></div>
              <div class="role-card-info"><div class="role-card-name">Class Teacher</div><div class="role-card-email">classteacher@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('teacher@schoolerp.in','Demo@2026', 'Teacher')">
              <div class="role-dot" style="background:#0284c7"></div>
              <div class="role-card-info"><div class="role-card-name">Teacher</div><div class="role-card-email">teacher@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('subjectteacher@schoolerp.in','Demo@2026', 'Subject Teacher')">
              <div class="role-dot" style="background:#0369a1"></div>
              <div class="role-card-info"><div class="role-card-name">Subject Teacher</div><div class="role-card-email">subjectteacher@schoolerp.in</div></div>
            </button>
          </div>
        </div>

        <!-- Operations & Admin -->
        <div class="role-group">
          <div class="role-group-title">Administration &amp; Operations</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('accountant@schoolerp.in','Demo@2026', 'Accountant')">
              <div class="role-dot" style="background:#16a34a"></div>
              <div class="role-card-info"><div class="role-card-name">Accountant</div><div class="role-card-email">accountant@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('hr@schoolerp.in','Demo@2026', 'HR Manager')">
              <div class="role-dot" style="background:#059669"></div>
              <div class="role-card-info"><div class="role-card-name">HR Manager</div><div class="role-card-email">hr@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('admissions@schoolerp.in','Demo@2026', 'Admissions')">
              <div class="role-dot" style="background:#0d9488"></div>
              <div class="role-card-info"><div class="role-card-name">Admissions</div><div class="role-card-email">admissions@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('reception@schoolerp.in','Demo@2026', 'Receptionist')">
              <div class="role-dot" style="background:#0891b2"></div>
              <div class="role-card-info"><div class="role-card-name">Receptionist</div><div class="role-card-email">reception@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('itadmin@schoolerp.in','Demo@2026', 'IT Admin')">
              <div class="role-dot" style="background:#475569"></div>
              <div class="role-card-info"><div class="role-card-name">IT Admin</div><div class="role-card-email">itadmin@schoolerp.in</div></div>
            </button>
          </div>
        </div>

        <!-- Campus Facilities -->
        <div class="role-group">
          <div class="role-group-title">Campus Facilities &amp; Services</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('librarian@schoolerp.in','Demo@2026', 'Librarian')">
              <div class="role-dot" style="background:#d97706"></div>
              <div class="role-card-info"><div class="role-card-name">Librarian</div><div class="role-card-email">librarian@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('transport@schoolerp.in','Demo@2026', 'Transport Mgr')">
              <div class="role-dot" style="background:#b45309"></div>
              <div class="role-card-info"><div class="role-card-name">Transport Mgr</div><div class="role-card-email">transport@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('hostelwarden@schoolerp.in','Demo@2026', 'Hostel Warden')">
              <div class="role-dot" style="background:#9333ea"></div>
              <div class="role-card-info"><div class="role-card-name">Hostel Warden</div><div class="role-card-email">hostelwarden@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('warden@schoolerp.in','Demo@2026', 'Warden')">
              <div class="role-dot" style="background:#a855f7"></div>
              <div class="role-card-info"><div class="role-card-name">Warden</div><div class="role-card-email">warden@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('inventory@schoolerp.in','Demo@2026', 'Inventory Mgr')">
              <div class="role-dot" style="background:#0284c7"></div>
              <div class="role-card-info"><div class="role-card-name">Inventory Mgr</div><div class="role-card-email">inventory@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('events@schoolerp.in','Demo@2026', 'Event Coordinator')">
              <div class="role-dot" style="background:#db2777"></div>
              <div class="role-card-info"><div class="role-card-name">Event Coord</div><div class="role-card-email">events@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('alumni@schoolerp.in','Demo@2026', 'Alumni Coordinator')">
              <div class="role-dot" style="background:#4338ca"></div>
              <div class="role-card-info"><div class="role-card-name">Alumni Coord</div><div class="role-card-email">alumni@schoolerp.in</div></div>
            </button>
          </div>
        </div>

        <!-- Portals -->
        <div class="role-group">
          <div class="role-group-title">Self-Service Portals</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('student@schoolerp.in','Demo@2026', 'Student')">
              <div class="role-dot" style="background:#059669"></div>
              <div class="role-card-info"><div class="role-card-name">Student Portal</div><div class="role-card-email">student@schoolerp.in</div></div>
            </button>
            <button class="role-btn-card" onclick="fillFromModal('parent@schoolerp.in','Demo@2026', 'Parent')">
              <div class="role-dot" style="background:#9333ea"></div>
              <div class="role-card-info"><div class="role-card-name">Parent Portal</div><div class="role-card-email">parent@schoolerp.in</div></div>
            </button>
          </div>
        </div>

      </div>

      <div class="demo-modal-foot">
        <span>Click any role to load credentials and close this window</span>
      </div>

    </div>
  </div>

<script>
function authLoginForm() {
  return {
    step: '{{ $errors->has('admin_pin') ? 'pin' : 'credentials' }}',
    loading: false,
    role: '{{ old('email') ? 'other' : 'admin' }}',
    email: '{{ old('email', '') }}',
    pin: '{{ old('admin_pin', app()->environment('local', 'testing') ? '1122' : '') }}',
    isEmailAdmin() {
      const e = (this.email || '').trim().toLowerCase();
      return e === 'admin@schoolerp.in' || e.startsWith('admin@') || e.startsWith('owner@') || e.startsWith('schooladmin@');
    },
    handleSubmit(e) {
      if (this.step === 'credentials') {
        const isAdmin = this.isEmailAdmin();
        if (isAdmin) {
          e.preventDefault();
          this.step = 'pin';
          this.$nextTick(() => {
            const p = document.getElementById('pin-field');
            if (p) {
              p.focus();
              p.select();
            }
          });
          return;
        }
      }
      this.loading = true;
    }
  };
}

function switchRole(roleKey, email, password) {
  const pillIds = ['tab-admin', 'tab-student', 'tab-parent', 'tab-teacher'];
  pillIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
  });

  const activePill = document.getElementById('tab-' + roleKey);
  if (activePill) activePill.classList.add('active');

  const sub = document.getElementById('roleSubtitle');
  if (sub) {
    const roleLabels = {
      admin: 'Signing in as Super Administrator',
      student: 'Signing in to Student Academic Portal',
      parent: 'Signing in to Parent Gateway',
      teacher: 'Signing in as Faculty / Teacher'
    };
    sub.textContent = roleLabels[roleKey] || 'Select your role or enter credentials';
  }

  const formEl = document.getElementById('login-form');
  if (formEl && formEl._x_dataStack && formEl._x_dataStack[0]) {
    formEl._x_dataStack[0].role = roleKey;
    formEl._x_dataStack[0].step = 'credentials';
    formEl._x_dataStack[0].email = email;
  }

  fillLogin(email, password);
}

function fillLogin(email, password) {
  const e = document.getElementById('email-field');
  const p = document.getElementById('pass-field');
  if (!e || !p) return;
  e.value = email;
  p.value = password;

  e.dispatchEvent(new Event('input', { bubbles: true }));
  p.dispatchEvent(new Event('input', { bubbles: true }));

  [e, p].forEach(el => {
    el.style.transition = 'border-color 0.2s, box-shadow 0.2s, background-color 0.2s';
    el.style.borderColor = '#a13431';
    el.style.backgroundColor = '#fff9f9';
    el.style.boxShadow = '0 0 0 3.5px rgba(161, 52, 49, 0.15)';
    setTimeout(() => {
      el.style.borderColor = '';
      el.style.backgroundColor = '';
      el.style.boxShadow = '';
    }, 600);
  });
}

function fillFromModal(email, password, roleName) {
  fillLogin(email, password);
  closeDemoModal();
  const sub = document.getElementById('roleSubtitle');
  if (sub && roleName) {
    sub.textContent = 'Selected role: ' + roleName;
  }
  const formEl = document.getElementById('login-form');
  if (formEl && formEl._x_dataStack && formEl._x_dataStack[0]) {
    const isAdm = roleName && (roleName.toLowerCase().includes('admin') || roleName.toLowerCase().includes('owner'));
    formEl._x_dataStack[0].role = isAdm ? 'admin' : 'other';
    formEl._x_dataStack[0].step = 'credentials';
    formEl._x_dataStack[0].email = email;
  }
}

function openDemoModal() {
  const el = document.getElementById('demo-overlay');
  if (el) {
    el.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}

function closeDemoModal(e) {
  const el = document.getElementById('demo-overlay');
  if (el && (!e || e.target === el)) {
    el.classList.remove('show');
    document.body.style.overflow = '';
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeDemoModal();
});
</script>
</body>
</html>
