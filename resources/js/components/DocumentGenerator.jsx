import React, { useState, useEffect } from 'react';
import '../../css/print-document.css';

/**
 * Enterprise ERP Document Generation & Print Viewer Component
 * 
 * Features:
 * - Content Isolation: Automatically excludes headers, sidebars & navigation via .no-print
 * - Dynamic Paper Management: A5, A4, A3, A2, A1 & CR80 ID Card in Portrait and Landscape
 * - Physical Print Sizing: Coordinates dynamic @page CSS rules directly with document.body
 * - Table Reporting: Multipage attendance and fee ledgers with repeating <thead> on every page
 * - Batch ID Cards: CR80 54x86mm formatting with forced physical page-breaks
 */
export default function DocumentGenerator({ 
  initialPaperSize = 'a4',
  initialOrientation = 'portrait',
  initialTemplate = 'circular',
  documentTitle = 'Official Document'
}) {
  const [paperSize, setPaperSize] = useState(initialPaperSize);       // 'a5' | 'a4' | 'a3' | 'a2' | 'a1' | 'id-card'
  const [orientation, setOrientation] = useState(initialOrientation); // 'portrait' | 'landscape'
  const [activeTemplate, setActiveTemplate] = useState(initialTemplate); // 'circular' | 'report' | 'id-cards'
  const [previewZoom, setPreviewZoom] = useState(100);

  // Sync active paper layout with document.body to trigger appropriate @page CSS rules
  useEffect(() => {
    if (typeof window !== 'undefined') {
      if (typeof window.setPrintPageSetup === 'function') {
        window.setPrintPageSetup(paperSize, orientation);
      } else {
        const size = (paperSize || 'a4').toUpperCase();
        const orient = orientation || 'portrait';
        let style = document.getElementById('global-print-page-style');
        if (!style) {
          style = document.createElement('style');
          style.id = 'global-print-page-style';
          document.head.appendChild(style);
        }
        const m = paperSize === 'a5' ? '12mm 15mm 15mm 15mm' : (paperSize === 'id-card' ? '3mm' : '16mm 18mm 18mm 18mm');
        const s = paperSize === 'id-card' ? (orient === 'landscape' ? '86mm 54mm' : '54mm 86mm') : `${size} ${orient}`;
        style.textContent = `@media print { @page { size: ${s}; margin: ${m}; } }`;

        const allSizes = ['paper-a5', 'paper-a4', 'paper-a3', 'paper-a2', 'paper-a1', 'paper-id-card', 'print-id-card', 'portrait', 'landscape'];
        document.body.classList.remove(...allSizes);
        document.body.classList.add(`paper-${paperSize}`, orient);
      }
    }

    return () => {
      document.body.classList.remove(
        'paper-a5', 'paper-a4', 'paper-a3', 'paper-a2', 'paper-a1', 'paper-id-card', 'print-id-card',
        'portrait', 'landscape'
      );
    };
  }, [paperSize, orientation]);

  const handleSizeChange = (e) => {
    const size = e.target.value;
    setPaperSize(size);
    if (size === 'id-card') {
      setActiveTemplate('id-cards');
    }
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="document-generator-root">
      {/* ─────────────────────────────────────────────────────────────
          1. TOOLBAR (Hidden in Print via .no-print)
          ───────────────────────────────────────────────────────────── */}
      <aside className="no-print print-toolbar">
        <div className="toolbar-container">
          <div className="toolbar-left">
            <span className="toolbar-brand">🖨️ Document Generation Engine</span>

            {/* Template Selector */}
            <div className="control-group">
              <label>Document Type:</label>
              <select
                value={activeTemplate}
                onChange={(e) => {
                  const t = e.target.value;
                  setActiveTemplate(t);
                  if (t === 'id-cards') setPaperSize('id-card');
                  else if (paperSize === 'id-card') setPaperSize('a4');
                }}
              >
                <option value="circular">Official Directive / Circular</option>
                <option value="report">Multi-Page Attendance &amp; Fee Ledger</option>
                <option value="id-cards">Student ID Cards (Batch CR80)</option>
              </select>
            </div>

            {/* Paper Size Selector */}
            <div className="control-group">
              <label>Paper Size:</label>
              <select value={paperSize} onChange={handleSizeChange}>
                <option value="a5">A5 (148 × 210 mm)</option>
                <option value="a4">A4 (210 × 297 mm)</option>
                <option value="a3">A3 (297 × 420 mm)</option>
                <option value="a2">A2 (420 × 594 mm)</option>
                <option value="a1">A1 (594 × 841 mm)</option>
                <option value="id-card">CR80 ID Card (54 × 86 mm)</option>
              </select>
            </div>

            {/* Orientation */}
            <div className="control-group">
              <label>Orientation:</label>
              <div className="btn-toggle-group">
                <button
                  type="button"
                  className={orientation === 'portrait' ? 'active' : ''}
                  onClick={() => setOrientation('portrait')}
                >
                  Portrait
                </button>
                <button
                  type="button"
                  className={orientation === 'landscape' ? 'active' : ''}
                  onClick={() => setOrientation('landscape')}
                >
                  Landscape
                </button>
              </div>
            </div>

            {/* Zoom Controls for Screen Preview */}
            <div className="control-group">
              <label>Preview Scale:</label>
              <select
                value={previewZoom}
                onChange={(e) => setPreviewZoom(Number(e.target.value))}
              >
                <option value={75}>75%</option>
                <option value={100}>100%</option>
                <option value={125}>125%</option>
                <option value={150}>150%</option>
              </select>
            </div>
          </div>

          <div className="toolbar-right">
            <button type="button" className="print-btn" onClick={handlePrint}>
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span>Print / Save as PDF</span>
            </button>
          </div>
        </div>
      </aside>

      {/* ─────────────────────────────────────────────────────────────
          2. SCREEN PREVIEW CANVAS & PRINTABLE DOCUMENT CONTAINER
          ───────────────────────────────────────────────────────────── */}
      <main className="document-preview-canvas">
        <div style={{ transform: `scale(${previewZoom / 100})`, transformOrigin: 'top center' }}>

          {/* TEMPLATE 1: OFFICIAL SCHOOL CIRCULAR / LETTERHEAD */}
          {activeTemplate === 'circular' && (
            <article className="paper-sheet official-letterhead-card">
              {/* Header */}
              <div className="letterhead-header avoid-break">
                <div className="school-brand-banner">
                  <div className="school-logo-emblem">EPS</div>
                  <div>
                    <h1 className="school-title">ERODE PUBLIC SCHOOL</h1>
                    <p className="school-sub">SENIOR SECONDARY • AFFILIATED TO CBSE, NEW DELHI (AFF. NO: 1930482)</p>
                    <p className="school-contact">Chennimalai Road, Erode, Tamil Nadu 638051 | Tel: +91 424 225 8000</p>
                  </div>
                </div>
                <hr className="divider" />
              </div>

              {/* Reference Bar */}
              <div className="meta-bar avoid-break">
                <div><strong>Ref No:</strong> EPS/CIR/2026/104</div>
                <div><strong>Date of Issue:</strong> October 29, 2026</div>
              </div>

              {/* Subject */}
              <div className="directive-subject avoid-break">
                <h3>Sub: Annual Practical Examination &amp; CBSE Assessment Guidelines 2026</h3>
              </div>

              {/* Directive Body */}
              <div className="document-body">
                <p>
                  This official directive is issued to notify all Heads of Departments, Faculty members, Parents, and Students regarding the upcoming CBSE Board Model Practicals and Senior Secondary Assessment evaluations.
                </p>
                <p>
                  1. Laboratory journals and internal assessment portfolios must be countersigned by respective subject coordinators no later than <strong>November 15, 2026</strong>.
                </p>
                <p>
                  2. Attendance minimum compliance of 75% as per CBSE Bye-Laws must be strictly verified. Special compensatory classes are designated on alternate Saturdays for eligible candidates.
                </p>
                <p>
                  3. Digital hall tickets will be made accessible via the DASA EduERP Student and Parent Gateways.
                </p>
              </div>

              {/* Signatory Block */}
              <div className="signatory-block avoid-break">
                <div>
                  <p><strong>Issuing Authority:</strong> Office of the Principal</p>
                  <p><strong>Copy to:</strong> Academic Coordinators • Notice Board • School ERP</p>
                </div>
                <div className="signature-right">
                  <div className="official-seal-box">[ OFFICIAL SEAL ]</div>
                  <p className="signer-name">Dr. R. Ramanathan, Ph.D.</p>
                  <p className="signer-title">Principal &amp; Head of Institution</p>
                </div>
              </div>
            </article>
          )}

          {/* TEMPLATE 2: MULTI-PAGE REPORT WITH REPEATING <thead> */}
          {activeTemplate === 'report' && (
            <article className="paper-sheet printable-report-container">
              <div className="avoid-break">
                <h2 style={{ fontSize: '15pt', fontWeight: '800', margin: '0 0 4px 0' }}>
                  TERM-II ACADEMIC FEE &amp; ATTENDANCE COMPLIANCE REPORT
                </h2>
                <p style={{ fontSize: '9.5pt', color: '#64748b', margin: '0 0 16px 0' }}>
                  Academic Year 2026–2027 • Generated: {new Date().toLocaleDateString()}
                </p>
              </div>

              <table className="printable-report-table">
                <thead>
                  <tr>
                    <th style={{ width: '8%' }}>Roll #</th>
                    <th style={{ width: '15%' }}>Adm. No</th>
                    <th style={{ width: '25%' }}>Student Name</th>
                    <th style={{ width: '15%' }}>Class &amp; Sec</th>
                    <th style={{ width: '17%' }}>Term II Fee</th>
                    <th style={{ width: '20%' }}>Attendance %</th>
                  </tr>
                </thead>
                <tbody>
                  {Array.from({ length: 48 }).map((_, i) => (
                    <tr key={i}>
                      <td>{i + 1}</td>
                      <td>EPS{202600 + i}</td>
                      <td>{['Aditya Sharma', 'Priya Raman', 'Kavitha S.', 'Mohammed Faizal', 'Rohit Varma', 'Deepika Nair'][i % 6]}</td>
                      <td>Class X - Section {String.fromCharCode(65 + (i % 3))}</td>
                      <td>{i % 3 === 0 ? '₹ 42,500 (Paid)' : '₹ 42,500 (Pending)'}</td>
                      <td>{(82 + (i % 18)).toFixed(1)}%</td>
                    </tr>
                  ))}
                </tbody>
                <tfoot>
                  <tr>
                    <td colSpan="4" style={{ textAlign: 'right', fontWeight: 'bold' }}>Total Cohort Summary:</td>
                    <td style={{ fontWeight: 'bold' }}>48 Students</td>
                    <td style={{ fontWeight: 'bold' }}>Avg: 91.4%</td>
                  </tr>
                </tfoot>
              </table>
            </article>
          )}

          {/* TEMPLATE 3: BATCH STUDENT ID CARDS (CR80 54x86mm with Forced Page Breaks) */}
          {activeTemplate === 'id-cards' && (
            <div className="id-card-batch-container">
              {[
                { id: 'EPSS101', name: 'Aarav Sundaram', grade: 'Class XII-A', blood: 'O+ve', valid: '2026–2027', phone: '98421 22001' },
                { id: 'EPSS102', name: 'Divya Bharathi', grade: 'Class XII-A', blood: 'B+ve', valid: '2026–2027', phone: '98421 22002' },
                { id: 'EPSS103', name: 'Karthik Raja',   grade: 'Class XII-B', blood: 'A+ve', valid: '2026–2027', phone: '98421 22003' }
              ].map((student, idx) => (
                <div key={idx} className="paper-sheet id-card-sheet print-id-card">
                  <div className="id-card-content">
                    {/* Header */}
                    <div className="id-header">
                      <div className="id-logo">EPS</div>
                      <div className="id-school-info">
                        <h5>ERODE PUBLIC SCHOOL</h5>
                        <p>SENIOR SECONDARY</p>
                      </div>
                    </div>

                    {/* Photo & Specs */}
                    <div className="id-body">
                      <div className="id-photo-placeholder">PHOTO</div>
                      <div className="id-name">{student.name}</div>
                      <div className="id-badge-role">STUDENT</div>

                      <div className="id-specs">
                        <div><span>ID No:</span> <strong>{student.id}</strong></div>
                        <div><span>Class:</span> <strong>{student.grade}</strong></div>
                        <div><span>Blood:</span> <strong>{student.blood}</strong></div>
                        <div><span>Emergency:</span> <strong>{student.phone}</strong></div>
                      </div>
                    </div>

                    {/* Footer */}
                    <div className="id-footer">
                      <span>Valid Thru: {student.valid}</span>
                      <span className="id-sign">Principal</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

        </div>
      </main>

      {/* Embedded Component Styling */}
      <style>{`
        .document-generator-root {
          display: flex;
          flex-direction: column;
          width: 100%;
          min-height: 100vh;
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .print-toolbar {
          position: sticky;
          top: 0;
          z-index: 50;
          background: #0f172a;
          color: #ffffff;
          padding: 0.75rem 1.5rem;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .toolbar-container {
          max-width: 1400px;
          margin: 0 auto;
          display: flex;
          align-items: center;
          justify-content: space-between;
          flex-wrap: wrap;
          gap: 1rem;
        }

        .toolbar-left {
          display: flex;
          align-items: center;
          gap: 1.25rem;
          flex-wrap: wrap;
        }

        .toolbar-brand {
          font-weight: 800;
          font-size: 0.95rem;
          letter-spacing: -0.01em;
          color: #f8fafc;
        }

        .control-group {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          font-size: 0.8rem;
          color: #cbd5e1;
        }

        .control-group select {
          background: #1e293b;
          color: #ffffff;
          border: 1px solid #334155;
          padding: 0.35rem 0.6rem;
          border-radius: 0.4rem;
          font-size: 0.8rem;
          outline: none;
        }

        .btn-toggle-group button {
          background: #1e293b;
          color: #94a3b8;
          border: 1px solid #334155;
          padding: 0.35rem 0.65rem;
          font-size: 0.75rem;
          font-weight: 600;
          cursor: pointer;
        }
        .btn-toggle-group button:first-child { border-radius: 0.4rem 0 0 0.4rem; }
        .btn-toggle-group button:last-child { border-radius: 0 0.4rem 0.4rem 0; border-left: none; }
        .btn-toggle-group button.active {
          background: #3b82f6;
          color: #ffffff;
          border-color: #3b82f6;
        }

        .print-btn {
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          background: #8c2826;
          color: #ffffff;
          border: none;
          padding: 0.5rem 1.15rem;
          border-radius: 0.5rem;
          font-weight: 700;
          font-size: 0.85rem;
          cursor: pointer;
          transition: background 0.15s ease;
        }
        .print-btn:hover { background: #731e1c; }

        .school-brand-banner { display: flex; align-items: center; gap: 1rem; }
        .school-logo-emblem {
          width: 50px; height: 50px; border-radius: 8px; background: #8c2826;
          color: white; font-weight: 900; display: flex; align-items: center; justify-content: center;
          font-size: 1.25rem; flex-shrink: 0;
        }
        .school-title { font-size: 1.4rem; font-weight: 900; color: #8c2826; margin: 0; }
        .school-sub { font-size: 0.65rem; font-weight: 700; color: #475569; margin: 2px 0 0 0; }
        .school-contact { font-size: 0.65rem; color: #64748b; margin: 2px 0 0 0; }
        .divider { border: none; border-bottom: 2px solid #8c2826; margin: 12px 0; }
        .meta-bar { display: flex; justify-content: space-between; font-size: 9pt; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 14px; }
        .directive-subject h3 { font-size: 11pt; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; }
        .document-body p { font-size: 10pt; line-height: 1.6; color: #1e293b; margin-bottom: 12px; }
        .signatory-block { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 35px; border-top: 1px solid #e2e8f0; padding-top: 14px; font-size: 8.5pt; }
        .signature-right { text-align: right; }
        .official-seal-box { width: 100px; height: 55px; border: 1px dashed #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 7.5pt; color: #94a3b8; margin: 0 0 4px auto; }
        .signer-name { font-weight: 800; font-size: 9.5pt; color: #0f172a; margin: 0; }
        .signer-title { font-size: 8pt; color: #64748b; margin: 0; }

        .id-card-content { height: 100%; display: flex; flex-direction: column; justify-content: space-between; font-size: 7pt; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 6px; box-sizing: border-box; background:#fff; }
        .id-header { display: flex; align-items: center; gap: 4px; border-bottom: 1.5px solid #8c2826; padding-bottom: 3px; }
        .id-logo { width: 18px; height: 18px; background: #8c2826; color: #fff; font-weight: 900; font-size: 7pt; display: flex; align-items: center; justify-content: center; border-radius: 3px; }
        .id-school-info h5 { margin: 0; font-size: 6.5pt; font-weight: 900; color: #8c2826; line-height: 1; }
        .id-school-info p { margin: 0; font-size: 5pt; color: #64748b; }
        .id-body { text-align: center; margin: 4px 0; }
        .id-photo-placeholder { width: 48px; height: 56px; border: 1px solid #cbd5e1; background: #f8fafc; margin: 0 auto 3px auto; display: flex; align-items: center; justify-content: center; font-size: 6pt; color: #94a3b8; }
        .id-name { font-size: 8pt; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .id-badge-role { display: inline-block; background: #8c2826; color: #fff; font-size: 5.5pt; font-weight: 700; padding: 1px 4px; border-radius: 2px; margin: 2px 0 4px 0; }
        .id-specs { text-align: left; font-size: 5.8pt; line-height: 1.35; }
        .id-specs span { color: #64748b; }
        .id-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 2px; font-size: 5.5pt; color: #64748b; }
        .id-sign { font-weight: bold; }
      `}</style>
    </div>
  );
}

