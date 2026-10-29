<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ERP Portal Sign In — Erode Public School Senior Secondary</title>
  <link rel="icon" type="image/png" href="{{ asset('images/school-seal-badge.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { min-height: 100%; height: 100%; }

    /* Top navigation progress loader */
    #dasa-nav-loader {
      background: linear-gradient(90deg, #8c2826 0%, #c8973a 50%, #a13431 100%) !important;
      box-shadow: 0 0 14px rgba(161, 52, 49, 0.9) !important;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #0f172a;
      color: #0f172a;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    /* ── Split Layout Container ── */
    .portal-root {
      display: flex;
      min-height: 100vh;
      width: 100%;
      position: relative;
    }

    /* ── Left Pane: Campus Visual & Institutional Showcase ── */
    .brand-hero-pane {
      display: none;
      width: 52%;
      position: relative;
      background: #3b0d0c;
      overflow: hidden;
      flex-direction: column;
      justify-content: space-between;
      padding: 3.25rem 3.5rem;
      z-index: 1;
    }

    @media (min-width: 1024px) {
      .brand-hero-pane {
        display: flex;
      }
    }

    /* Background image with layered gradients */
    .brand-hero-bg {
      position: absolute;
      inset: 0;
      background-image: 
        linear-gradient(145deg, rgba(58, 12, 11, 0.94) 0%, rgba(98, 22, 20, 0.90) 45%, rgba(25, 7, 7, 0.96) 100%),
        url('{{ asset("images/school_campus_bg.jpg") }}');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      z-index: 1;
      transform: scale(1.02);
      transition: transform 12s ease-out;
    }

    .brand-hero-pane:hover .brand-hero-bg {
      transform: scale(1.06);
    }

    /* Subtle geometric mesh glow */
    .brand-hero-mesh {
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 15% 20%, rgba(200, 151, 58, 0.22) 0%, transparent 45%),
                  radial-gradient(circle at 85% 85%, rgba(161, 52, 49, 0.35) 0%, transparent 50%);
      z-index: 2;
      pointer-events: none;
    }

    .brand-hero-content {
      position: relative;
      z-index: 3;
      display: flex;
      flex-direction: column;
      height: 100%;
      justify-content: space-between;
    }

    /* Top Brand Badge in Hero */
    .hero-brand-top {
      display: flex;
      align-items: center;
      gap: 1.25rem;
    }

    .hero-crest-badge {
      width: 4.8rem;
      height: 4.8rem;
      border-radius: 1.25rem;
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid rgba(200, 151, 58, 0.5);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 0 20px rgba(200, 151, 58, 0.3);
      padding: 0.4rem;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .hero-crest-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .hero-title-group {
      display: flex;
      flex-direction: column;
    }

    .hero-school-title {
      font-size: 1.65rem;
      font-weight: 900;
      color: #ffffff;
      letter-spacing: -0.01em;
      line-height: 1.15;
      text-transform: uppercase;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    }

    .hero-school-sub {
      font-size: 1.1rem;
      font-weight: 800;
      color: #f6d895;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .hero-cbse-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: rgba(200, 151, 58, 0.18);
      border: 1px solid rgba(246, 216, 149, 0.4);
      backdrop-filter: blur(8px);
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 0.72rem;
      font-weight: 700;
      color: #fef08a;
      letter-spacing: 0.02em;
      margin-top: 0.35rem;
      width: fit-content;
    }

    /* Hero Middle Feature Showcase */
    .hero-showcase {
      margin: 2.5rem 0;
    }

    .hero-headline {
      font-size: 2.15rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.25;
      letter-spacing: -0.02em;
      margin-bottom: 0.85rem;
      text-shadow: 0 3px 12px rgba(0, 0, 0, 0.35);
    }

    .hero-headline span {
      background: linear-gradient(135deg, #fef08a 0%, #f6d895 50%, #eab308 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-desc {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.85);
      line-height: 1.6;
      max-width: 480px;
      font-family: 'Inter', sans-serif;
      margin-bottom: 2rem;
    }

    /* Translucent Value Cards */
    .hero-cards-grid {
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
      max-width: 500px;
    }

    .hero-card-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      border-radius: 1rem;
      padding: 0.95rem 1.15rem;
      transition: all 0.25s ease;
    }

    .hero-card-item:hover {
      background: rgba(255, 255, 255, 0.13);
      border-color: rgba(246, 216, 149, 0.4);
      transform: translateX(4px);
    }

    .hero-card-icon {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 0.75rem;
      background: linear-gradient(135deg, rgba(200, 151, 58, 0.3) 0%, rgba(161, 52, 49, 0.4) 100%);
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fef08a;
      flex-shrink: 0;
    }

    .hero-card-text h4 {
      font-size: 0.88rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 0.15rem;
    }

    .hero-card-text p {
      font-size: 0.77rem;
      color: rgba(255, 255, 255, 0.75);
      line-height: 1.45;
      font-family: 'Inter', sans-serif;
    }

    /* Hero Footer Trust Banner */
    .hero-footer-trust {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
      padding-top: 1.25rem;
      margin-top: 1.5rem;
    }

    .trust-stat {
      display: flex;
      flex-direction: column;
    }

    .trust-stat-val {
      font-size: 1.25rem;
      font-weight: 800;
      color: #ffffff;
    }

    .trust-stat-lbl {
      font-size: 0.7rem;
      color: rgba(255, 255, 255, 0.65);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .trust-badge-row {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .trust-pill {
      font-size: 0.68rem;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.85);
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.15);
      padding: 0.25rem 0.6rem;
      border-radius: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    /* ── Right Pane: Modern Auth Panel ── */
    .auth-canvas-pane {
      flex: 1;
      min-height: 100vh;
      background: #f8fafc;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow-y: auto;
      padding: 1.5rem 1rem;
    }

    @media (min-width: 640px) {
      .auth-canvas-pane {
        padding: 2.5rem 2rem;
      }
    }

    @media (min-width: 1200px) {
      .auth-canvas-pane {
        padding: 3rem 4rem;
      }
    }

    /* Ambient background gradient in auth pane */
    .auth-pane-glow {
      position: absolute;
      top: 0;
      right: 0;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(140, 40, 38, 0.04) 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }

    /* Mobile School Header (< 1024px) */
    .mobile-brand-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding: 0.85rem 1rem;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
      position: relative;
      z-index: 2;
    }

    @media (min-width: 1024px) {
      .mobile-brand-bar {
        display: none;
      }
    }

    .mobile-crest-img {
      height: 3.25rem;
      width: auto;
      object-fit: contain;
    }

    .mobile-brand-info {
      text-align: left;
    }

    .mobile-brand-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: #8c2826;
      line-height: 1.15;
      text-transform: uppercase;
    }

    .mobile-brand-sub {
      font-size: 0.72rem;
      font-weight: 800;
      color: #8c2826;
      letter-spacing: 0.03em;
    }

    .mobile-brand-cbse {
      font-size: 0.65rem;
      color: #64748b;
      font-weight: 600;
      margin-top: 0.1rem;
    }

    /* Auth Main Center Container */
    .auth-center-wrap {
      max-width: 460px;
      width: 100%;
      margin: 0 auto;
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    /* Main Auth Card */
    .auth-elevated-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 1.35rem;
      padding: 2.25rem 1.85rem;
      box-shadow: 
        0 1px 3px 0 rgba(0, 0, 0, 0.03),
        0 15px 35px -5px rgba(15, 23, 42, 0.07),
        0 0 0 1px rgba(226, 232, 240, 0.6);
      position: relative;
    }

    @media (min-width: 480px) {
      .auth-elevated-card {
        padding: 2.5rem 2.25rem;
      }
    }

    /* Card Header */
    .auth-card-head {
      margin-bottom: 1.5rem;
      text-align: left;
    }

    .portal-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background: #fdf2f2;
      border: 1px solid #fecaca;
      color: #8c2826;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 0.2rem 0.6rem;
      border-radius: 9999px;
      margin-bottom: 0.5rem;
    }

    .auth-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.025em;
      line-height: 1.2;
    }

    .auth-subtitle {
      font-size: 0.85rem;
      color: #64748b;
      margin-top: 0.3rem;
      font-weight: 500;
      font-family: 'Inter', sans-serif;
    }

    /* Role Segmented Tabs */
    .role-segmented-box {
      margin-bottom: 1.35rem;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 0.85rem;
      padding: 0.35rem;
    }

    .role-grid-tabs {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.25rem;
    }

    .role-pill-tab {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.25rem;
      padding: 0.55rem 0.2rem;
      border-radius: 0.65rem;
      border: none;
      background: transparent;
      cursor: pointer;
      color: #475569;
      font-size: 0.73rem;
      font-weight: 600;
      font-family: 'Plus Jakarta Sans', sans-serif;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      touch-action: manipulation;
    }

    .role-pill-tab:hover {
      color: #8c2826;
      background: rgba(255, 255, 255, 0.7);
    }

    .role-pill-tab.active {
      background: #8c2826;
      color: #ffffff;
      font-weight: 700;
      box-shadow: 0 4px 10px rgba(140, 40, 38, 0.35);
    }

    .role-tab-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.45rem 0.4rem 0.15rem;
      border-top: 1px solid #e2e8f0;
      margin-top: 0.35rem;
    }

    .role-hint-txt {
      font-size: 0.68rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .role-modal-trigger {
      font-size: 0.72rem;
      font-weight: 700;
      color: #8c2826;
      background: none;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.25rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      padding: 0.15rem 0.35rem;
      border-radius: 0.4rem;
      transition: background 0.15s ease;
    }

    .role-modal-trigger:hover {
      background: #fdf2f2;
      text-decoration: underline;
    }

    /* Flash Alerts */
    .auth-alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      margin-bottom: 1.25rem;
      color: #991b1b;
      font-size: 0.8125rem;
      display: flex;
      align-items: flex-start;
      gap: 0.65rem;
      line-height: 1.45;
    }

    /* Form Field Groups */
    .auth-field-group {
      margin-bottom: 1.25rem;
    }

    .auth-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.45rem;
    }

    .auth-input-container {
      position: relative;
      display: flex;
      align-items: center;
    }

    .auth-input-icon {
      position: absolute;
      left: 1rem;
      color: #94a3b8;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
    }

    .auth-field-input {
      width: 100%;
      border: 1.5px solid #cbd5e1;
      border-radius: 0.75rem;
      padding: 0.8rem 1rem 0.8rem 2.85rem;
      font-size: 0.925rem;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
      font-family: 'Inter', sans-serif;
    }

    .auth-field-input:focus {
      border-color: #8c2826;
      box-shadow: 0 0 0 4px rgba(140, 40, 38, 0.12);
      background-color: #ffffff;
    }

    .auth-field-input:focus ~ .auth-input-icon {
      color: #8c2826;
    }

    .auth-field-input::placeholder {
      color: #94a3b8;
    }

    .auth-eye-btn {
      position: absolute;
      right: 0.85rem;
      background: none;
      border: none;
      cursor: pointer;
      color: #94a3b8;
      padding: 0.35rem;
      display: flex;
      align-items: center;
      border-radius: 0.4rem;
      transition: color 0.15s, background-color 0.15s;
    }

    .auth-eye-btn:hover {
      color: #334155;
      background: #f1f5f9;
    }

    /* Helper callout for first time staff login */
    .staff-login-callout {
      margin-top: 0.45rem;
      background: #fdf8f6;
      border: 1px solid #fed7aa;
      border-radius: 0.6rem;
      padding: 0.45rem 0.65rem;
      font-size: 0.72rem;
      color: #9a3412;
      line-height: 1.4;
      display: flex;
      align-items: flex-start;
      gap: 0.4rem;
    }

    .staff-login-callout strong {
      color: #8c2826;
    }

    /* Row for Remember Me & Forgot Password */
    .auth-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .auth-checkbox-label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8125rem;
      color: #475569;
      cursor: pointer;
      user-select: none;
      font-weight: 500;
    }

    .auth-checkbox {
      width: 1.05rem;
      height: 1.05rem;
      accent-color: #8c2826;
      cursor: pointer;
      border-radius: 0.25rem;
    }

    .auth-forgot-link {
      font-size: 0.8125rem;
      color: #8c2826;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s;
    }

    .auth-forgot-link:hover {
      color: #631715;
      text-decoration: underline;
    }

    /* High Impact CTA Button */
    .auth-submit-btn {
      width: 100%;
      padding: 0.9rem 1.25rem;
      background: linear-gradient(135deg, #8c2826 0%, #a13431 100%);
      color: #ffffff;
      border: none;
      border-radius: 0.75rem;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.01em;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      font-family: 'Plus Jakarta Sans', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.55rem;
      min-height: 50px;
      box-shadow: 0 4px 12px rgba(140, 40, 38, 0.3);
      touch-action: manipulation;
    }

    .auth-submit-btn:hover:not(:disabled) {
      background: linear-gradient(135deg, #78211f 0%, #8c2826 100%);
      box-shadow: 0 8px 20px rgba(140, 40, 38, 0.4);
      transform: translateY(-1.5px);
    }

    .auth-submit-btn:active:not(:disabled) {
      transform: translateY(0);
      box-shadow: 0 3px 8px rgba(140, 40, 38, 0.3);
    }

    .auth-submit-btn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* ── Step 2: Admin Security PIN View ── */
    .pin-verify-box {
      text-align: center;
    }

    .pin-shield-circle {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      border: 2px solid #fecaca;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem auto;
      box-shadow: 0 6px 16px rgba(140, 40, 38, 0.15);
      position: relative;
    }

    .pin-shield-circle::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 50%;
      border: 1px solid rgba(140, 40, 38, 0.2);
      animation: pulseBorder 2s infinite;
    }

    @keyframes pulseBorder {
      0% { transform: scale(1); opacity: 0.8; }
      50% { transform: scale(1.1); opacity: 0; }
      100% { transform: scale(1); opacity: 0; }
    }

    .pin-heading {
      font-size: 1.35rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.02em;
      margin-bottom: 0.25rem;
    }

    .pin-subheading {
      font-size: 0.825rem;
      color: #64748b;
      margin-bottom: 1.25rem;
    }

    .pin-account-badge {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.65rem 0.95rem;
      margin-bottom: 1.5rem;
    }

    .pin-account-email {
      font-size: 0.825rem;
      font-weight: 700;
      color: #1e293b;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .pin-input-field {
      width: 100%;
      max-width: 240px;
      margin: 0 auto;
      text-align: center;
      font-size: 1.75rem;
      letter-spacing: 0.5em;
      font-weight: 900;
      padding: 0.75rem 0.5rem;
      border-radius: 0.85rem;
      border: 2px solid #cbd5e1;
      background: #ffffff;
      color: #0f172a;
      outline: none;
      transition: all 0.2s ease;
      font-family: monospace, sans-serif;
    }

    .pin-input-field:focus {
      border-color: #8c2826;
      box-shadow: 0 0 0 4px rgba(140, 40, 38, 0.15);
    }

    .pin-back-btn {
      width: 100%;
      background: none;
      border: none;
      font-size: 0.825rem;
      font-weight: 600;
      color: #64748b;
      padding: 0.65rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-top: 0.5rem;
      border-radius: 0.5rem;
      transition: color 0.15s, background-color 0.15s;
    }

    .pin-back-btn:hover {
      color: #0f172a;
      background: #f1f5f9;
    }

    /* Auth Canvas Footer */
    .auth-bottom-footer {
      margin-top: 2rem;
      padding-top: 1rem;
      text-align: center;
      font-size: 0.75rem;
      color: #64748b;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      align-items: center;
      position: relative;
      z-index: 2;
    }

    .auth-footer-links {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
      flex-wrap: wrap;
    }

    .auth-footer-dot {
      color: #cbd5e1;
    }

    .dasa-brand-txt {
      font-weight: 700;
      color: #334155;
    }

    /* ── All 23 Roles Directory Modal ── */
    .demo-overlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 99999;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(5px);
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
      border-radius: 1.35rem;
      width: 100%;
      max-width: 740px;
      max-height: calc(100vh - 3rem);
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3);
      animation: modalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      margin: auto;
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }

    @keyframes modalPop {
      from { opacity: 0; transform: translateY(14px) scale(0.97); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .demo-modal-head {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .demo-modal-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 1.1rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    .demo-modal-crest {
      width: 1.85rem;
      height: 1.85rem;
      object-fit: contain;
    }

    .demo-modal-sub {
      font-size: 0.77rem;
      color: #64748b;
      margin-top: 0.15rem;
    }

    .demo-close-btn {
      width: 2.15rem;
      height: 2.15rem;
      border-radius: 0.6rem;
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
      padding: 1.35rem 1.5rem;
      flex: 1;
      -webkit-overflow-scrolling: touch;
    }

    .pw-badge-box {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      background: #fdf2f2;
      border: 1px solid #fecaca;
      border-radius: 0.75rem;
      padding: 0.65rem 0.95rem;
      font-size: 0.8rem;
      font-weight: 600;
      color: #8c2826;
      margin-bottom: 1.35rem;
      width: 100%;
    }

    .role-group {
      margin-bottom: 1.35rem;
    }

    .role-group-title {
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 0.55rem;
      padding-left: 0.15rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .role-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 0.45rem;
    }

    @media (min-width: 480px) { .role-grid { grid-template-columns: 1fr 1fr; } }
    @media (min-width: 640px) { .role-grid { grid-template-columns: 1fr 1fr 1fr; } }

    .role-btn-card {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      padding: 0.6rem 0.75rem;
      border-radius: 0.75rem;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      text-align: left;
      touch-action: manipulation;
    }

    .role-btn-card:hover {
      border-color: #8c2826;
      background: #fff5f5;
      box-shadow: 0 4px 12px rgba(140, 40, 38, 0.1);
      transform: translateY(-1px);
    }

    .role-btn-card:hover .role-card-name {
      color: #8c2826;
    }

    .role-dot {
      width: 0.55rem;
      height: 0.55rem;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .role-card-info {
      min-width: 0;
      flex: 1;
    }

    .role-card-name {
      font-size: 0.8rem;
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
      padding: 0.85rem 1.5rem;
      border-top: 1px solid #e2e8f0;
      background: #f8fafc;
      flex-shrink: 0;
      text-align: center;
      font-size: 0.78rem;
      color: #64748b;
      font-weight: 500;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin 0.7s linear infinite; }
  </style>
</head>
<body>
@include('partials.mobile-nav-speed')

  <div class="portal-root">

    <!-- ── 1. LEFT PANE: Institutional Brand & Campus Pride Showcase ── -->
    <aside class="brand-hero-pane">
      <div class="brand-hero-bg"></div>
      <div class="brand-hero-mesh"></div>

      <div class="brand-hero-content">

        <!-- Top School Crest & Title -->
        <div class="hero-brand-top">
          <div class="hero-crest-badge">
            <img
              src="{{ asset('images/school-logo.png') }}"
              alt="Erode Public School Crest"
              class="hero-crest-img"
              onerror="this.src='{{ asset('images/school-seal-badge.png') }}'"
            >
          </div>
          <div class="hero-title-group">
            <h1 class="hero-school-title">ERODE PUBLIC SCHOOL</h1>
            <span class="hero-school-sub">SENIOR SECONDARY</span>
            <div class="hero-cbse-pill">
              <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <span>Affiliated to CBSE, New Delhi &bull; Aff. No. 1931585</span>
            </div>
          </div>
        </div>

        <!-- Middle Feature Showcase -->
        <div class="hero-showcase">
          <h2 class="hero-headline">
            Empowering Minds,<br>
            <span>Shaping Tomorrow.</span>
          </h2>
          <p class="hero-desc">
            Welcome to the centralized digital campus portal. Streamlining academic excellence, administrative intelligence, and unified parent-teacher collaboration.
          </p>

          <div class="hero-cards-grid">
            <!-- Feature 1 -->
            <div class="hero-card-item">
              <div class="hero-card-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
              </div>
              <div class="hero-card-text">
                <h4>Academics &amp; Digital Gradebook</h4>
                <p>CBSE curriculum compliance, section allocations, live attendance tracking &amp; automated report cards.</p>
              </div>
            </div>

            <!-- Feature 2 -->
            <div class="hero-card-item">
              <div class="hero-card-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              </div>
              <div class="hero-card-text">
                <h4>Unified Multi-Stakeholder Gateway</h4>
                <p>Role-governed workspaces tailored for Management, Teachers, Staff, Students &amp; Guardians.</p>
              </div>
            </div>

            <!-- Feature 3 -->
            <div class="hero-card-item">
              <div class="hero-card-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              </div>
              <div class="hero-card-text">
                <h4>Smart Campus Operations</h4>
                <p>Automated timetable scheduling, transport route management, and fee collection ledgers.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Hero Footer Trust & Accreditations -->
        <div class="hero-footer-trust">
          <div class="trust-stat">
            <span class="trust-stat-val">800+</span>
            <span class="trust-stat-lbl">Active Enrolled Students</span>
          </div>
          <div class="trust-badge-row">
            <span class="trust-pill">
              <svg width="12" height="12" fill="none" stroke="#22c55e" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
              CBSE Affiliated
            </span>
            <span class="trust-pill">
              <svg width="12" height="12" fill="none" stroke="#f59e0b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              256-Bit SSL
            </span>
          </div>
        </div>

      </div>
    </aside>

    <!-- ── 2. RIGHT PANE: Modern Auth Workspace ── -->
    <main class="auth-canvas-pane">
      <div class="auth-pane-glow"></div>

      <!-- Mobile Top Brand (< 1024px) -->
      <div class="mobile-brand-bar">
        <img
          src="{{ asset('images/school-logo.png') }}"
          alt="Erode Public School"
          class="mobile-crest-img"
          onerror="this.src='{{ asset('images/school-seal-badge.png') }}'"
        >
        <div class="mobile-brand-info">
          <h2 class="mobile-brand-title">Erode Public School</h2>
          <span class="mobile-brand-sub">Senior Secondary &bull; CBSE</span>
          <div class="mobile-brand-cbse">Affiliation No. 1931585 &bull; New Delhi</div>
        </div>
      </div>

      <!-- Auth Center Card -->
      <div class="auth-center-wrap">

        <div class="auth-elevated-card" x-data="authLoginForm()">

          <!-- Step 1: Credentials Form Header & Tabs -->
          <div x-show="step === 'credentials'">
            <div class="auth-card-head">
              <span class="portal-tag">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Institutional ERP Portal
              </span>
              <h2 class="auth-title">Portal Sign In</h2>
              <p class="auth-subtitle" id="roleSubtitle">Select your role or enter credentials to continue</p>
            </div>

            <!-- Quick Role Switcher -->
            <div class="role-segmented-box">
              <div class="role-grid-tabs">
                <!-- Admin -->
                <button type="button" class="role-pill-tab active" id="tab-admin" onclick="switchRole('admin', 'admin@schoolerp.in', 'Admin@1234')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>Admin</span>
                </button>

                <!-- Teacher -->
                <button type="button" class="role-pill-tab" id="tab-teacher" onclick="switchRole('teacher', 'teacher@schoolerp.in', 'Demo@2026')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                  <span>Teacher</span>
                </button>

                <!-- Student -->
                <button type="button" class="role-pill-tab" id="tab-student" onclick="switchRole('student', 'student@schoolerp.in', 'Demo@2026')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  <span>Student</span>
                </button>

                <!-- Parent -->
                <button type="button" class="role-pill-tab" id="tab-parent" onclick="switchRole('parent', 'parent@schoolerp.in', 'Demo@2026')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <span>Parent</span>
                </button>
              </div>

              <div class="role-tab-footer">
                <span class="role-hint-txt">
                  <svg width="12" height="12" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                  1-Click Demo Fill
                </span>
                <button type="button" class="role-modal-trigger" onclick="openDemoModal()">
                  <span>All 23 Roles Directory</span>
                  <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Flash Error Alert -->
          @if($errors->any())
            <div class="auth-alert-error">
              <svg style="width:1.2rem;height:1.2rem;flex-shrink:0;margin-top:0.05rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>{{ $errors->first() }}</span>
            </div>
          @endif

          <!-- Form Element -->
          <form id="login-form" method="POST" action="{{ route('login') }}" @submit="handleSubmit($event)">
            @csrf

            <!-- STEP 1: Credentials Section (Email/ID & Password) -->
            <div x-show="step === 'credentials'">
              <!-- Email / Employee ID / Mobile -->
              <div class="auth-field-group">
                <label class="auth-label" for="email-field">Email Address, Employee ID, or Mobile</label>
                <div class="auth-input-container">
                  <input
                    id="email-field"
                    type="text"
                    name="email"
                    x-model="email"
                    required
                    autofocus
                    class="auth-field-input"
                    placeholder="e.g. EPSS9790, name@schoolerp.in, or mobile"
                  >
                  <div class="auth-input-icon">
                    <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                  </div>
                </div>
                @error('email')<p style="font-size:0.75rem; color:#dc2626; margin-top:0.35rem;">{{ $message }}</p>@enderror
              </div>

              <!-- Password -->
              <div class="auth-field-group" x-data="{ show: false }">
                <label class="auth-label" for="pass-field">Password</label>
                <div class="auth-input-container">
                  <input
                    id="pass-field"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    class="auth-field-input"
                    style="padding-right: 2.85rem;"
                    placeholder="••••••••"
                  >
                  <div class="auth-input-icon">
                    <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                  </div>
                  <button
                    type="button"
                    class="auth-eye-btn"
                    @click="show = !show"
                    title="Toggle password visibility"
                    aria-label="Toggle password visibility"
                  >
                    <svg x-show="!show" style="width:1.15rem;height:1.15rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show"  style="width:1.15rem;height:1.15rem" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                  </button>
                </div>
                
                <!-- Staff First Time Login Info -->
                <div class="staff-login-callout">
                  <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:0.1rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <div>
                    Staff first-time login: Enter your Employee ID (e.g. <strong>EPSS9790</strong>) and your Date of Birth in DDMMYYYY format (e.g. <strong>15091998</strong>).
                  </div>
                </div>
              </div>

              <!-- Remember Me & Forgot Password -->
              <div class="auth-options-row">
                <label class="auth-checkbox-label">
                  <input type="checkbox" name="remember" class="auth-checkbox" checked>
                  <span>Keep me signed in</span>
                </label>
                <a href="#" class="auth-forgot-link">Forgot password?</a>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="auth-submit-btn" :disabled="loading">
                <span x-show="!loading" style="display:flex;align-items:center;gap:0.5rem">
                  <span>Sign In to ERP Portal</span>
                  <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </span>
                <span x-show="loading" style="display:flex;align-items:center;gap:0.5rem">
                  <svg class="spinner" style="width:1.2rem;height:1.2rem" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                  <span>Authenticating…</span>
                </span>
              </button>
            </div>

            <!-- STEP 2: Security PIN Verification (Admin Accounts) -->
            <div x-show="step === 'pin'" style="display: none;">
              <div class="pin-verify-box">
                <!-- Shield Icon -->
                <div class="pin-shield-circle">
                  <svg width="30" height="30" fill="none" stroke="#8c2826" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                </div>
                <h3 class="pin-heading">Enter Security PIN</h3>
                <p class="pin-subheading">Two-step administrative authentication required</p>

                <!-- Active User Pill -->
                <div class="pin-account-badge">
                  <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; flex-shrink: 0;"></div>
                    <span class="pin-account-email" x-text="email || 'admin@schoolerp.in'"></span>
                  </div>
                  <button type="button" @click="step = 'credentials'" style="font-size: 0.75rem; font-weight: 700; color: #8c2826; background: none; border: none; cursor: pointer; text-decoration: underline;">
                    Change
                  </button>
                </div>

                <!-- PIN Input -->
                <div style="margin-bottom: 1.5rem;">
                  <label class="auth-label" for="pin-field" style="text-align: center; margin-bottom: 0.5rem;">
                    Admin Security PIN
                  </label>
                  <input
                    id="pin-field"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    name="admin_pin"
                    x-model="pin"
                    class="pin-input-field"
                    placeholder="••••"
                    autocomplete="one-time-code"
                  >
                  @error('admin_pin')<p style="font-size:0.75rem; color:#dc2626; text-align:center; margin-top:0.4rem;">{{ $message }}</p>@enderror

                  @if(app()->environment('local', 'testing'))
                  <div style="margin-top: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                    <span style="font-size: 0.72rem; color: #64748b;">Dev Default:</span>
                    <button type="button" @click="pin = '1122'" style="font-size: 0.72rem; font-weight: 700; color: #8c2826; background: #fdf2f2; border: 1px solid #fecaca; border-radius: 0.4rem; padding: 0.15rem 0.45rem; cursor: pointer;">
                      1122 (Auto-fill)
                    </button>
                  </div>
                  @endif
                </div>

                <!-- Verify Button -->
                <button type="submit" class="auth-submit-btn" :disabled="loading" style="margin-bottom: 0.75rem;">
                  <span x-show="!loading" style="display:flex;align-items:center;gap:0.5rem">
                    <span>Verify PIN &amp; Access Portal</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  </span>
                  <span x-show="loading" style="display:flex;align-items:center;gap:0.5rem">
                    <svg class="spinner" style="width:1.2rem;height:1.2rem" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    <span>Authorizing…</span>
                  </span>
                </button>

                <!-- Back to Credentials -->
                <button type="button" @click="step = 'credentials'" class="pin-back-btn">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                  <span>Back to login credentials</span>
                </button>
              </div>
            </div>

          </form>

        </div>

      </div>

      <!-- Footer Info -->
      <footer class="auth-bottom-footer">
        <div class="auth-footer-links">
          <span>&copy; {{ date('Y') }} Erode Public School</span>
          <span class="auth-footer-dot">&bull;</span>
          <span>CBSE Senior Secondary (1931585)</span>
          <span class="auth-footer-dot">&bull;</span>
          <span>Powered by <span class="dasa-brand-txt">DASA Tech</span></span>
        </div>
        <div style="font-size: 0.7rem; color: #94a3b8;">
          Authorized institutional access only &bull; Monitored session
        </div>
      </footer>

    </main>

  </div>

  {{-- ── All 23 Roles Directory Modal ── --}}
  <div class="demo-overlay" id="demo-overlay" onclick="closeDemoModal(event)">
    <div class="demo-modal" onclick="event.stopPropagation()">

      <div class="demo-modal-head">
        <div>
          <div class="demo-modal-title">
            <img src="{{ asset('images/school-seal-badge.png') }}" alt="Crest" class="demo-modal-crest" onerror="this.style.display='none'">
            <span>All 23 Role Demo Accounts Directory</span>
          </div>
          <div class="demo-modal-sub">Click any role card to auto-load credentials &amp; switch context</div>
        </div>
        <button class="demo-close-btn" onclick="closeDemoModal()" title="Close dialog" aria-label="Close">
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="demo-modal-body">

        <div class="pw-badge-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <div>All role passwords: <strong>Demo@2026</strong> &nbsp;|&nbsp; Super Admin: <strong>Admin@1234</strong></div>
        </div>

        <!-- Management -->
        <div class="role-group">
          <div class="role-group-title">Executive &amp; Leadership</div>
          <div class="role-grid">
            <button class="role-btn-card" onclick="fillFromModal('admin@schoolerp.in','Admin@1234', 'Super Admin')">
              <div class="role-dot" style="background:#8c2826"></div>
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
        <span>Click any role to auto-populate credentials and close this window</span>
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
    el.style.borderColor = '#8c2826';
    el.style.backgroundColor = '#fff7f7';
    el.style.boxShadow = '0 0 0 3.5px rgba(140, 40, 38, 0.15)';
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
