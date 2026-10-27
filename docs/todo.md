# School ERP — Master TODO List
**Project:** DC Innovision School ERP  
**Stack:** Laravel 12 + Tailwind CSS v3 + Alpine.js  
**Last Updated:** June 2026

> This file lists every task still pending. Completed items are in `school_erp_feature_checklist.md`.  
> UI standards are in `ui_design_system.md` — all new views must follow those specs.

---

## PRIORITY 1 — UI Overhaul (Apply Design System)

The existing 10 modules have working backend + basic views. All views need to be upgraded to match `ui_design_system.md`.

- [ ] Apply global Tailwind config additions (Inter + Plus Jakarta Sans fonts, custom colors, shadows, animations)
- [ ] Rebuild `layouts/app.blade.php` — sidebar with gradient (slate-900 → slate-800), logo, nav groups, user avatar at bottom
- [ ] Sidebar: active state with left border accent + blue glow, collapsed icon-only mode (64px), smooth 250ms transition
- [ ] Page header component — title + breadcrumb + right-side action buttons on every page
- [ ] KPI stat cards — gradient icon circles, count-up animation on dashboard load
- [ ] Table component — rounded-2xl wrapper, gradient header row, hover:bg-blue-50/40, empty-state with SVG illustration
- [ ] Badge/chip component — semantic colors (green/red/amber/blue/slate) per status type
- [ ] Primary button — gradient blue→indigo, hover shadow-blue-200, active scale-97
- [ ] Modal — glass card, scale+fade animation, overlay backdrop-blur
- [ ] Side drawer — 480px right-panel for add/edit forms, slide-in from right
- [ ] Toast notifications — slide-in from right, border-left colored strip (success/error/warning/info)
- [ ] Login page — full-page gradient bg (#1D4ED8 → #4F46E5), glass card form, school logo
- [ ] Dashboard — count-up JS on KPI numbers, Chart.js for line + donut charts
- [ ] Form inputs — rounded-xl, focus:ring-blue-500/30, error state (red border + message below)
- [ ] File upload zones — dashed border, hover:border-blue-400 drag-and-drop visual
- [ ] Skeleton loader — animated pulse blocks for page load states
- [ ] Mobile responsive — off-canvas sidebar drawer, FAB add button, table horizontal scroll
- [ ] 404 / error page — animated gradient, friendly message

---

## PRIORITY 2 — Feature Gaps in Existing Modules (Modules 1–10)

### Module 1 — Admissions
- [ ] File upload support in enquiry form
- [ ] Duplicate enquiry detection (same mobile + class)
- [ ] Admin email notification on new enquiry
- [ ] Enquiry confirmation SMS to parent
- [ ] Follow-up date scheduling with reminder
- [ ] Bulk status update for selected enquiries
- [ ] Export enquiry list to Excel/CSV
- [ ] Application form builder (admin configures fields per academic year + class)
- [ ] Online application form — multi-step wizard
- [ ] Document uploads on application form (Birth Cert, Aadhaar, TC, Photo, Caste, Address)
- [ ] Application fee collection (Razorpay)
- [ ] Admission confirmation letter PDF
- [ ] Rejection letter + email/SMS notification
- [ ] Waitlist management with position number + promotion trigger
- [ ] Category-wise seat reservation tracking
- [ ] Prevent over-admission beyond seat limit (with admin override)
- [ ] Year-over-year enquiry comparison chart
- [ ] Counsellor-wise conversion report
- [ ] Daily/weekly/monthly enquiry trend report

### Module 2 — Students
- [ ] Passport number + expiry field
- [ ] Passport-size photo upload with crop tool
- [ ] Physical disability status field
- [ ] Permanent address field (copy-from-residential button)
- [ ] Annual family income field
- [ ] Sibling discount auto-trigger on sibling linking
- [ ] Previous school marks/percentage field
- [ ] Migration certificate number + upload
- [ ] Health insurance policy number field
- [ ] House assignment (Red/Blue/Green/Yellow or custom)
- [ ] Student type: Day Scholar / Hosteller / Day Boarder
- [ ] Sibling group view — all students of same family
- [ ] Sibling concession configurable % and auto-apply
- [ ] Merit-based scholarship criteria (marks threshold)
- [ ] Scholarship sanction letter upload
- [ ] Scholarship renewal reminder before expiry
- [ ] ID card template designer (logo, colours, fields)
- [ ] Barcode / QR on student ID card
- [ ] Warning letter PDF for disciplinary action
- [ ] Suspension/expulsion record with dates
- [ ] Positive behaviour awards log
- [ ] Parent notification on disciplinary action
- [ ] Document expiry tracking (e.g., medical certificate)
- [ ] Reminder on expiring documents
- [ ] TC template configuration (letterhead, principal signature)
- [ ] TC issue register
- [ ] Prevent duplicate TC (admin override required)
- [ ] TC request workflow: parent → HOD → Principal → Issue
- [ ] Promotion rules from exam results (pass/fail criteria)
- [ ] Rollover carry-forward: fee balances, library dues
- [ ] Mark left/transferred students with TC
- [ ] Academic year archive (previous year read-only)
- [ ] Student directory with photo grid/list toggle
- [ ] Quick-view profile panel on hover/click

### Module 3 — Academics
- [ ] Terms/semesters within academic year
- [ ] Working days configuration per week
- [ ] Academic calendar PDF export for parents
- [ ] Copy holiday list from previous year
- [ ] Co-class teacher assignment
- [ ] Section rename/merge/split utility
- [ ] Language subjects: 1st / 2nd / 3rd language flags
- [ ] Elective vs compulsory subject flag
- [ ] Board curriculum tagging (CBSE / State / Both)
- [ ] Subject credit hours per week
- [ ] Subject group/stream (Science/Commerce/Arts for XI–XII)
- [ ] Syllabus PDF upload per subject per term
- [ ] Lesson plan approval workflow (HOD review)
- [ ] Syllabus coverage % auto-calculated
- [ ] Lesson plan print as PDF
- [ ] Syllabus completion report per class/subject
- [ ] Teacher workload report (periods per week)
- [ ] Maximum workload limit + over-allocation alert
- [ ] Define break/lunch periods in timetable
- [ ] Auto-generate timetable (constraint-based)
- [ ] Teacher-wise timetable view
- [ ] Conflict detection (teacher double-booked)
- [ ] Substitution management (absent teacher → suggest substitute)
- [ ] Substitution register
- [ ] Timetable effective date (mid-year change)
- [ ] Print timetable PDF (class-wise + teacher-wise)
- [ ] Homework file attachments (PDF, image, doc)
- [ ] Mark homework submitted per student
- [ ] Mark homework evaluated with score/remarks
- [ ] Overdue homework alert to student/parent
- [ ] Homework calendar view
- [ ] Homework completion % report per class
- [ ] Competency master and competency-based assessment
- [ ] 360-degree/co-scholastic assessment entry
- [ ] Notice board & circulars (create, target audience, attach PDF)
- [ ] Push notification + SMS on new circular
- [ ] Read receipt tracking per notice

### Module 4 — Attendance
- [ ] Reason capture for absence
- [ ] Late arrival time logging
- [ ] Early departure time logging
- [ ] Lock attendance after cutoff time (configurable)
- [ ] Admin override locked attendance with reason log
- [ ] Period-wise attendance report per subject per student
- [ ] Biometric device integration (ZKTeco, Suprema)
- [ ] RFID card mapping + gate entry/exit log
- [ ] Auto SMS/WhatsApp to parent on absence
- [ ] Configurable notification trigger time
- [ ] Late arrival + early departure SMS to parent
- [ ] Condonation of attendance by principal
- [ ] Exam hall ticket block for shortage students
- [ ] Approved leave reflected as "On Leave" (not counted as absent)
- [ ] Medical certificate upload for sick leave
- [ ] Biometric punch-in/out sync for staff
- [ ] Late arrival tracking for staff
- [ ] Staff attendance dashboard (today's summary)
- [ ] Staff attendance vs leave reconciliation
- [ ] Class-wise attendance summary with period-wise breakdown
- [ ] Chronic absentee report (configurable threshold)
- [ ] Date-wise school strength report
- [ ] Teacher attendance report

### Module 5 — Examinations
- [ ] Grace marks configuration
- [ ] Internal vs external exam flag
- [ ] GPA/CGPA grading scheme
- [ ] Co-scholastic grading (A/B/C/D for activities)
- [ ] Question paper template builder
- [ ] Manual question paper assembly from bank
- [ ] Question paper access control (restricted until exam date)
- [ ] QR code on hall ticket for verification
- [ ] Hall ticket block for fee defaulters
- [ ] Hall ticket block for attendance shortage students
- [ ] Grace marks entry (tracked separately)
- [ ] Marks import via Excel template
- [ ] Marks lock after submission (admin unlock with log)
- [ ] Marks entry progress indicator (% entered per class)
- [ ] Marks recheck request workflow
- [ ] Best-of-N-subjects logic (CBSE X/XII)
- [ ] Cumulative marks across terms with weightage
- [ ] CGPA calculation
- [ ] Subject-wise highest/lowest/average marks
- [ ] Supplementary exam eligibility flag
- [ ] Co-scholastic areas display on report card
- [ ] Attendance summary on report card
- [ ] Class teacher + principal remarks on report card
- [ ] Graphical performance bar/radar chart on report card
- [ ] Bulk report card PDF generation (entire class)
- [ ] Digital signature/stamp on report card
- [ ] Report card portal download
- [ ] PDF export of tabulation register
- [ ] Online exam module (MCQ, T/F, fill-in-blank, short answer)
- [ ] Auto-submit on timer expiry
- [ ] Random question order per student
- [ ] MCQ auto-evaluation + instant result
- [ ] Bulk SMS/WhatsApp/email result notifications to parents
- [ ] Class-wise result summary: pass%, toppers
- [ ] Subject-wise performance report
- [ ] Student-wise result history across all exams
- [ ] Failed student list with subjects failed
- [ ] Comparative class vs school average report

### Module 6 — Fees
- [ ] Category-wise fee variation (General/SC/ST different amounts)
- [ ] Individual student custom fee override
- [ ] Monthly/quarterly/half-yearly/annual payment options
- [ ] New admission fee vs existing student fee differentiation
- [ ] GST applicability per fee head (%, HSN code)
- [ ] Bulk assign fee structure to class
- [ ] Auto-assign fee on student enrollment
- [ ] Fee revision: bulk update amount mid-year
- [ ] Fee change history log per student
- [ ] Online payment via Razorpay
- [ ] Online payment via PayU
- [ ] Payment webhook (success/failure)
- [ ] Advance payment + carry-forward
- [ ] Cheque bounce handling + bounce charge
- [ ] GST-compliant invoice with GSTIN + HSN
- [ ] Duplicate receipt reprint
- [ ] Fine displayed separately on receipt
- [ ] Fine collection report
- [ ] Staff ward fee concession scheme
- [ ] Concession report for management
- [ ] Automated fee reminder SMS/WhatsApp/email
- [ ] Reminder schedule configurable (before/on/after due date)
- [ ] Defaulter aging report (0–30, 31–60, 61–90, 90+ days)
- [ ] Defaulter list Excel export
- [ ] Defaulter demand notice PDF
- [ ] Block portal access for long-term defaulters
- [ ] Monthly collection report vs previous month
- [ ] Annual collection: expected vs collected vs outstanding
- [ ] Class-wise + fee-head-wise collection reports
- [ ] Outstanding balance report with contact details
- [ ] Cashier-wise collection report
- [ ] Bank reconciliation report
- [ ] Tally ledger mapping configuration
- [ ] GST details in Tally export
- [ ] Parent portal: fee statement, pay online, receipt download

### Module 7 — HR & Payroll
- [ ] Employee photo upload
- [ ] Qualification details (multi-entry)
- [ ] Previous experience records (multi-entry)
- [ ] Certification and training records
- [ ] Employee document uploads (Aadhaar, PAN, Degree, etc.)
- [ ] PF account number, ESI number fields
- [ ] Grade/pay band per designation
- [ ] Reporting hierarchy
- [ ] Staff ID card (separate template from student card)
- [ ] Leave balance tracker per employee
- [ ] Leave cancellation request
- [ ] Leave history per employee
- [ ] Half-day leave support
- [ ] Leave encashment calculation (EL)
- [ ] Leave balance carry-forward rules
- [ ] Leave report: balance/availed/pending per employee
- [ ] Attendance-linked pay: auto-deduct LOP days
- [ ] Overtime calculation
- [ ] Arrears and bonus addition
- [ ] Advance salary and recovery tracking
- [ ] Loan management (amount, EMI, outstanding)
- [ ] Salary hold for specific employee
- [ ] Payroll approval workflow
- [ ] Payroll lock after approval
- [ ] PF computation (12% employee + 12% employer)
- [ ] ESI computation (0.75% + 3.25%)
- [ ] Professional Tax (PT) slab-based computation
- [ ] TDS computation + Form 16 data
- [ ] PT / PF / ESI challan generation
- [ ] Bulk payslip PDF for all employees
- [ ] Payslip email delivery / portal download
- [ ] Password-protected payslip PDF
- [ ] Bank-specific NEFT file format (SBI, HDFC, Axis, ICICI)
- [ ] Annual appraisal form + self-appraisal workflow
- [ ] Increment processing linked to appraisal
- [ ] Increment letter PDF
- [ ] Relieving letter PDF
- [ ] No-objection certificate (NOC) PDF
- [ ] Department-wise salary summary
- [ ] PF/ESI/PT monthly statement
- [ ] Annual TDS summary

### Module 8 — Library
- [ ] ISBN auto-fill via Open Library API
- [ ] Book cover image upload
- [ ] Deaccession (write-off) book with reason
- [ ] Bulk book import via Excel
- [ ] Barcode/QR auto-generate per book copy
- [ ] Print barcode labels (single or bulk)
- [ ] Barcode scan on issue-return screen
- [ ] Staff members auto-synced from HR module
- [ ] Active/suspended/graduated member status
- [ ] Per-member borrowing limit configuration
- [ ] Fine collection integration with Fee module
- [ ] Issue/return receipt (printable)
- [ ] Book renewal (extend due date, max 1 renewal)
- [ ] Lost book handling (mark lost, charge replacement cost)
- [ ] SMS notification when reserved book is returned
- [ ] Fine rate per day configuration (member-type specific)
- [ ] Fine collection record (cash/portal)
- [ ] Fine outstanding per member report
- [ ] Auto SMS overdue reminders (due date + 3 days after)
- [ ] Access control for digital resources (specific classes)
- [ ] Currently issued books list
- [ ] Overdue books with contact details
- [ ] Most borrowed books (popularity ranking)
- [ ] Member-wise borrowing history
- [ ] Annual stock audit report
- [ ] Acquisition report
- [ ] Deaccession register

### Module 9 — Transport
- [ ] Distance from school per stop
- [ ] Google Maps embed for route visualisation
- [ ] Attendant master (name, mobile, Aadhaar, photo)
- [ ] License expiry alert for driver
- [ ] Police verification status tracking
- [ ] Transport fee auto-link based on stop/distance
- [ ] Seat availability check before new assignment
- [ ] GPS live tracking (admin dashboard + parent portal)
- [ ] Estimated arrival time per stop
- [ ] Geofence: alert on school entry/exit + route deviation
- [ ] Bus attendant marks students boarded/not-boarded
- [ ] Automatic SMS to parent on board/alight
- [ ] Next service reminder (by date or km)
- [ ] SOS alert button for driver/attendant
- [ ] Breakdown reporting + parent notification
- [ ] Accident/incident log
- [ ] Vehicle utilisation report
- [ ] Fuel expense monthly summary
- [ ] Maintenance cost per vehicle report
- [ ] Bus attendance report

### Module 10 — Hostel
- [ ] Floor master per block
- [ ] Bed master per room (individual bed IDs)
- [ ] Room amenities (AC, locker, attached bathroom)
- [ ] Transfer student to different room/bed
- [ ] Vacate student from hostel (with reason)
- [ ] Mess fee inclusion/exclusion option
- [ ] Fee auto-link to main Fee Management module
- [ ] Daily mess attendance per meal
- [ ] Mess rebate for absentees
- [ ] Mess feedback from students
- [ ] Mess expense tracking
- [ ] Assign warden(s) to hostel block
- [ ] Warden duty roster + night duty assignment
- [ ] Parent approval for outing (portal notification)
- [ ] Gate pass QR code generation
- [ ] Late return alert to warden and parent
- [ ] Photo capture of hostel visitor
- [ ] Allowed visiting hours enforcement
- [ ] Visitor pass with time-limited QR
- [ ] Warning issuance for hostel discipline
- [ ] Parent notification on hostel discipline
- [ ] Complaint assigned to staff/vendor
- [ ] Complaint history per room
- [ ] Current occupancy report
- [ ] Room-wise student list
- [ ] Hostel fee outstanding list
- [ ] Mess attendance summary
- [ ] Maintenance complaint status report

---

## PRIORITY 3 — New Modules (Not Yet Built)

### Module 11 — Parent & Student Portal
- [ ] Secure login (email + password + OTP option)
- [ ] Password reset via OTP
- [ ] Session timeout + failed login lockout
- [ ] Parent dashboard: attendance, fee dues, results, notices, timetable, bus tracking
- [ ] Student dashboard: timetable, homework, marks, attendance, notices
- [ ] Month-wise attendance calendar (colour-coded)
- [ ] Fee statement + Razorpay/PayU pay-now button + receipt download
- [ ] Syllabus, homework, study materials section
- [ ] Exam schedule, hall ticket download, report card download
- [ ] Progress chart (graphical performance over terms)
- [ ] In-portal notice board with read status
- [ ] Parent-teacher messaging (inbox/sent)
- [ ] Parent: edit mobile, email, address + change password
- [ ] Notification preferences (SMS/WhatsApp/email on/off)
- [ ] Multiple children support (switch child from same login)

### Module 12 — Communication & Notifications
- [ ] SMS gateway: MSG91 / Textlocal / Twilio — API key config, credit balance display
- [ ] WhatsApp Business API (360Dialog / Meta Cloud)
- [ ] SMTP bulk email with HTML template builder
- [ ] Bulk SMS + WhatsApp + email composer with audience targeting
- [ ] Message preview + recipient count before send
- [ ] Auto-trigger notifications: absence, fee due, results, birthday, low attendance, TC ready
- [ ] Firebase FCM push notifications
- [ ] Internal staff notice board with read receipts
- [ ] Full message log + failed delivery retry
- [ ] DND filter for SMS

### Module 13 — Analytics Dashboard
- [ ] Executive KPI dashboard (strength, attendance %, today's fee, outstanding, new admissions, staff present)
- [ ] Admission funnel + source chart + monthly trend
- [ ] Attendance heatmap + chronic absentee alert
- [ ] Exam performance comparison (Term 1 vs Term 2 vs Annual)
- [ ] Fee collection vs outstanding donut chart + monthly trend
- [ ] Custom report builder (drag-and-drop fields, filters, sort, save, export)
- [ ] Scheduled report delivery via email (cron-based)
- [ ] Standard official registers: General Register, TC register, Caution Deposit, Fee Collection Register

### Module 14 — Inventory & Store
- [ ] Item master with category, unit, reorder level
- [ ] Vendor master with item-vendor mapping
- [ ] Purchase Requisition → PO → GRN workflow
- [ ] PO PDF generation + email to vendor
- [ ] GST input credit tracking on purchases
- [ ] Stock issuance and return register
- [ ] Uniform + book store with student billing
- [ ] Low stock alert to store in-charge
- [ ] Annual stock audit (physical vs system)
- [ ] Stock ledger, purchase report, issuance report, budget utilisation

### Module 15 — Events & Calendar
- [ ] Event creation (type, audience, cover image, attachment, recurring)
- [ ] Monthly / weekly / list calendar view with colour-coded event types
- [ ] Exam dates + homework due dates overlaid on calendar
- [ ] RSVP for parents (PTM, open events)
- [ ] Event attendance marking + report
- [ ] Photo gallery per event (bulk upload, parent-visible)
- [ ] Birthday auto-detect + SMS/WhatsApp wish
- [ ] iCal export for Google Calendar / Outlook

### Module 16 — Visitor & Gate Pass
- [ ] Walk-in visitor registration (name, ID proof, photo, purpose, vehicle)
- [ ] SMS notification to staff on visitor arrival
- [ ] Staff approve/reject visit from portal
- [ ] Entry + exit timestamp + duration + overstay alert
- [ ] Student outpass: class teacher approval + parent OTP + QR gate pass
- [ ] Pre-registered visitor check-in (faster flow)
- [ ] Blacklist management with auto-alert on entry attempt
- [ ] Daily visitor log + outpass register

### Module 17 — LMS (Online Learning)
- [ ] Course builder: Unit → Chapter → Lesson (drag-and-drop tree)
- [ ] Lesson types: Video (upload or YouTube embed), PDF, Text/WYSIWYG, Quiz, Assignment
- [ ] Schedule live class (Zoom/Meet/Teams link + SMS reminder 15 min before)
- [ ] Student file assignment submission + teacher evaluation
- [ ] Quiz builder: MCQ, T/F, fill-in-blank; auto-evaluate; random question order
- [ ] Student progress tracking (% complete per course)
- [ ] Discussion forum per course (moderated)
- [ ] Certificate of completion (PDF with QR verification)

### Module 18 — Alumni Management
- [ ] Alumni self-registration form (verified against SIS by admission no)
- [ ] Alumni profile: current job, company, location, LinkedIn, higher education
- [ ] Searchable alumni directory with privacy settings
- [ ] Achievement announcements + newsletter to all alumni
- [ ] Alumni events: invite, RSVP, photo gallery
- [ ] Online donation campaigns with Razorpay + 80G receipt

### Module 20 — System Administration
- [ ] RBAC UI: create custom roles, assign module-level permissions (View/Create/Edit/Delete/Export)
- [ ] Field-level restriction (hide sensitive fields from certain roles)
- [ ] User account management: bulk create, deactivate, password reset, force-change on first login
- [ ] Login activity log per user (last login, IP)
- [ ] Multi-academic year: view previous years read-only, year-end closing workflow
- [ ] Audit trail: log all create/update/delete with old/new values, user, IP
- [ ] Scheduled daily DB backup (mysqldump via cron) + download by Super Admin
- [ ] School branding: logo, letterhead, principal signature upload, color theme
- [ ] SMS / WhatsApp / SMTP / FCM / Payment gateway API key configuration page
- [ ] HTTPS enforcement, rate limiting on login/OTP, admin IP whitelist
- [ ] Multi-language support: Tamil + Hindi UI translation
- [ ] PDPB consent capture + Aadhaar masking across all UI
- [ ] REST API with Sanctum auth + Postman/Swagger docs
- [ ] Webhook endpoints: payment gateway, SMS delivery reports, GPS device push

---

## PRIORITY 4 — Deployment (Shared Hosting)

- [ ] Configure PHP 8.2 extensions on hosting (BCMath, cURL, GD, Zip, etc.)
- [ ] Point document root to `public/` directory
- [ ] `.htaccess` Apache URL rewriting
- [ ] SSL certificate (Let's Encrypt)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `php artisan storage:link`
- [ ] Cron job: `* * * * * php artisan schedule:run`
- [ ] Queue worker via scheduler (not daemon): `php artisan queue:work --stop-when-empty`
- [ ] `php artisan config:cache && route:cache && view:cache` post-deploy
- [ ] Backup retention cron (keep last 30 days)
- [ ] Test queued PDF generation and bulk SMS on shared hosting

---

## Quick Reference — What's Done vs Pending

| Module | Backend | Views | UI Polish | All Features |
|--------|---------|-------|-----------|--------------|
| 1 Admissions | ✅ | ✅ | ❌ | ~60% |
| 2 Students | ✅ | ✅ | ❌ | ~65% |
| 3 Academics | ✅ | ✅ | ❌ | ~50% |
| 4 Attendance | ✅ | ✅ | ❌ | ~55% |
| 5 Examinations | ✅ | ✅ | ❌ | ~60% |
| 6 Fees | ✅ | ✅ | ❌ | ~55% |
| 7 HR & Payroll | ✅ | ✅ | ❌ | ~55% |
| 8 Library | ✅ | ✅ | ❌ | ~50% |
| 9 Transport | ✅ | ✅ | ❌ | ~55% |
| 10 Hostel | ✅ | ✅ | ❌ | ~50% |
| 11 Portal | ❌ | ❌ | ❌ | 0% |
| 12 Communication | ❌ | ❌ | ❌ | 0% |
| 13 Analytics | ❌ | ❌ | ❌ | 0% |
| 14 Inventory | ❌ | ❌ | ❌ | 0% |
| 15 Events | ❌ | ❌ | ❌ | 0% |
| 16 Visitor/Gate | ❌ | ❌ | ❌ | 0% |
| 17 LMS | ❌ | ❌ | ❌ | 0% |
| 18 Alumni | ❌ | ❌ | ❌ | 0% |
| 20 System Admin | ❌ | ❌ | ❌ | 0% |

---

*DC Innovision Pvt Ltd | School ERP v1.0 | June 2026*
