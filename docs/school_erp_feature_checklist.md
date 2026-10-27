# School ERP System — Complete Feature Checklist
**Project:** DC Innovision School ERP
**Stack:** PHP 8.2 + Laravel 12 | MySQL 8.x | Blade + Alpine.js + Tailwind CSS v3
**Deployment:** Shared Web Hosting (XAMPP local dev)
**Version:** 1.0 | Date: June 2026

> **Note:** AI & Analytics module and heavy VPS-only features (Redis daemon, WebSocket server, AWS S3, background queue workers) are excluded for shared hosting deployment. Local disk storage, sync/cron-based queues, and file-based caching are used instead.

---

## Legend
- `[ ]` Not Started
- `[x]` Completed

---

## MODULE 1 — Admission & Enquiry Management

### 1.1 Enquiry Form
- [x] Online enquiry form (public-facing, no login required)
- [x] Fields: student name, DOB, class applying for, parent name, mobile, email, address
- [x] Enquiry source tracking: Walk-in, Website, Referral, Social Media, Advertisement
- [x] Referral name/staff capture when source = Referral
- [ ] File upload support in enquiry form (optional documents)
- [x] Auto-generate unique enquiry ID on submission
- [ ] Duplicate enquiry detection (same mobile + class)
- [x] Enquiry date and time auto-stamp
- [ ] Admin notification email on new enquiry submission
- [ ] Enquiry confirmation SMS to parent on submission

### 1.2 Enquiry Management (Admin)
- [x] List all enquiries with filters: class, source, status, date range
- [x] Search enquiries by name, mobile, enquiry ID
- [x] Enquiry status workflow: New → Contacted → Test Scheduled → Interviewed → Confirmed → Rejected → Waitlisted
- [x] Manual status update with remarks/notes
- [x] Assign enquiry to specific staff/counsellor
- [ ] Follow-up date scheduling with reminder
- [x] Follow-up history log per enquiry (who called, when, outcome)
- [ ] Bulk status update for selected enquiries
- [ ] Export enquiry list to Excel/CSV

### 1.3 Application Form
- [ ] Application form builder (admin configures fields)
- [ ] Generate application form link per academic year + class
- [ ] Online application form with multi-step wizard
- [ ] Document upload fields: Birth Certificate, Aadhaar Card, Transfer Certificate, Passport Photo, Caste Certificate, Address Proof
- [ ] Document type validation (PDF, JPG, PNG; max size config)
- [ ] Application fee collection integration (Razorpay)
- [ ] Auto-generate application number on submission
- [ ] Application submitted confirmation email + SMS to parent
- [ ] Admin view all submitted applications with document preview
- [ ] Application form print/download as PDF

### 1.4 Admission Workflow
- [x] Multi-stage admission pipeline: Enquiry → Application → Entrance Test → Interview → Document Verification → Confirmation → Enrolled
- [x] Stage-wise status update with date and remarks
- [ ] Schedule entrance test: date, time, venue, invigilator
- [ ] Entrance test hall ticket generation (PDF)
- [ ] Entrance test marks entry
- [ ] Schedule interview: date, time, interviewer
- [ ] Interview feedback and score entry
- [ ] Document verification checklist per student
- [ ] Flag missing documents with notification to parent
- [ ] Admission confirmation letter generation (PDF)
- [ ] Rejection with reason capture
- [ ] Rejection notification email + SMS to parent
- [ ] Waitlist management with position number
- [ ] Waitlist promotion when seat opens (manual trigger + notification)

### 1.5 Seat & Capacity Management
- [x] Define seat capacity per class, section, and category (General/SC/ST/OBC/EWS)
- [x] Real-time seat availability counter on enquiry form
- [ ] Category-wise seat reservation and tracking
- [x] Seats filled / available / waitlisted dashboard
- [ ] Prevent over-admission beyond seat limit (configurable override for admin)

### 1.6 Bulk Admission
- [x] Excel template download for bulk student import
- [x] Bulk import validation: duplicate check, mandatory field check
- [x] Import error report with row-level error description
- [x] Preview imported records before final save
- [x] Bulk confirm and enroll imported students

### 1.7 Analytics & Reporting
- [x] Enquiry conversion funnel: Enquiries → Applications → Admitted (count + %)
- [x] Source-wise enquiry breakdown chart
- [x] Class-wise admission progress vs seat capacity
- [ ] Year-over-year enquiry and admission comparison
- [ ] Counsellor-wise conversion report
- [ ] Daily/weekly/monthly enquiry trend report
- [ ] Export all reports to PDF and Excel

---

## MODULE 2 — Student Information System (SIS)

### 2.1 Student Profile — Personal Information
- [x] Student full name (first, middle, last)
- [x] Date of birth with age auto-calculation
- [x] Gender
- [x] Nationality, religion, caste, sub-caste, category (General/SC/ST/OBC/EWS/Minority)
- [x] Mother tongue / first language
- [x] Aadhaar number (masked display)
- [ ] Passport number and expiry (if applicable)
- [x] Blood group
- [ ] Passport-size photo upload with crop tool
- [ ] Physical disability status and description
- [x] Student email address
- [x] Student mobile number (for senior students)
- [x] Residential address with pincode
- [ ] Permanent address (copy from residential option)

### 2.2 Student Profile — Family Information
- [x] Father: name, occupation, mobile, email
- [x] Mother: name, occupation, mobile, email
- [x] Guardian (if different): name, relation, mobile, email
- [ ] Annual family income
- [x] Number of siblings (sibling_group_id linking)
- [ ] Sibling discount auto-trigger on linking
- [x] Emergency contact person name, relation, mobile

### 2.3 Student Profile — Academic History
- [x] Previous school name
- [x] TC number and date from previous school
- [ ] Previous school marks / percentage
- [ ] Migration certificate number and date
- [ ] Upload previous TC, marksheet, migration cert

### 2.4 Student Profile — Medical Information
- [x] Known allergies (food, medicine, environmental)
- [x] Chronic medical conditions
- [x] Current medications list
- [x] Vaccination records: vaccine name, date, dose
- [x] Family doctor name and contact
- [x] Nearest hospital preference
- [ ] Health insurance policy number (optional)
- [x] Medical fitness certificate upload

### 2.5 Student Enrollment Details
- [x] Student unique ID (system-generated, permanent)
- [x] Admission number (school-specific format, configurable)
- [x] Admission date
- [x] Academic year of admission
- [x] Class and section assignment
- [x] Roll number assignment (manual or auto-sequential)
- [ ] House assignment (Red/Blue/Green/Yellow or custom)
- [ ] Bus route and stop assignment
- [ ] Hostel room assignment (if applicable)
- [ ] Student type: Day Scholar / Hosteller / Day Boarder

### 2.6 Sibling Management
- [ ] Search and link siblings within the system
- [ ] Sibling group view (all students of same family)
- [ ] Automatic sibling fee concession trigger on linking
- [ ] Sibling concession percentage configurable per school
- [ ] Sibling concession reflected in fee structure automatically

### 2.7 Scholarship & Concession Management
- [x] Define scholarship schemes: name, type (%, flat amount), applicable fee heads
- [ ] Merit-based scholarship criteria (marks threshold)
- [x] Government scholarship categories (SC/ST/OBC/Minority/EWS)
- [x] Manual scholarship/concession grant by admin
- [ ] Upload scholarship sanction letter
- [x] Scholarship valid from/to date
- [x] Scholarship reflected in fee challan automatically
- [ ] Scholarship renewal reminder before expiry

### 2.8 Student ID Card
- [ ] ID card template designer (logo, school name, colours, fields)
- [x] Auto-populate: photo, name, class, section, admission no, DOB, blood group, emergency contact
- [ ] Barcode or QR code embedding (linked to student profile)
- [x] Print single / bulk ID cards
- [x] PDF export of ID cards
- [x] Academic year validity printed on card

### 2.9 Behaviour & Disciplinary Records
- [x] Log disciplinary incident: date, type, description, action taken, staff who reported
- [x] Incident categories: Misconduct, Absenteeism, Bullying, Property Damage, Cheating, Others
- [ ] Warning letter generation (PDF)
- [ ] Suspension/expulsion record with dates
- [x] Disciplinary record history view per student
- [ ] Positive behaviour awards log
- [ ] Parent notification on disciplinary action

### 2.10 Student Documents Store
- [x] Central document upload per student (multi-file)
- [x] Document categories: Aadhaar, Birth Certificate, Caste Certificate, TC, Marksheet, Medical, Other
- [x] Document verification status: Pending / Verified / Rejected
- [x] Verified by (staff name) and date
- [ ] Document expiry tracking (e.g., medical certificate)
- [ ] Reminder on expiring documents

### 2.11 Transfer Certificate (TC) Generation
- [ ] TC template configuration (school letterhead, principal signature)
- [x] Auto-fill TC fields from student profile
- [x] TC serial number with auto-increment and year prefix
- [x] TC fields: Name, DOB, Admission No, Class last studied, conduct, reason for leaving, date
- [x] TC issue date and last working day
- [x] Print TC as PDF with official formatting
- [ ] TC issue register (log of all TCs issued)
- [ ] Prevent duplicate TC issuance (require admin override)
- [ ] TC request workflow: parent request → HOD approval → Principal sign-off → Issue

### 2.12 Student Promotion & Rollover
- [x] Year-end bulk promotion: promote all students class-wise
- [ ] Promotion rules: pass/fail criteria from exam results
- [x] Manual override: promote/hold/detain specific students
- [x] Assign new class, section, roll number after promotion
- [ ] Rollover carry-forward: fee balances, library dues
- [ ] Students who leave: mark as Left/Transferred with TC
- [ ] Academic year archive: previous year data accessible read-only
- [ ] Bulk rollover progress report

### 2.13 Student Search & Directory
- [x] Global search by name, admission no, roll no, mobile, Aadhaar (partial match)
- [x] Filter by class, section, gender, category, house, status (active/inactive)
- [ ] Student directory with photo grid / list view toggle
- [x] Export filtered student list to Excel/PDF
- [ ] Quick view panel (profile summary on hover/click)

---

## MODULE 3 — Academic Management

### 3.1 Academic Year & Calendar Setup
- [x] Create academic year: name, start date, end date
- [ ] Define terms/semesters within academic year (Term 1, Term 2, etc.)
- [ ] Term start and end dates
- [ ] Working days configuration per week (Mon–Sat or Mon–Fri)
- [x] Holiday master: date, holiday name, type (National/State/School/Optional)
- [x] Holiday calendar view (monthly grid)
- [ ] Academic calendar PDF export (shareable with parents)
- [ ] Copy holiday list from previous year as template

### 3.2 Class & Section Configuration
- [x] Create classes: LKG, UKG, I–XII (configurable names)
- [x] Create sections per class: A, B, C… (configurable count)
- [x] Set student capacity per section
- [x] Assign class teacher to each section
- [ ] Assign co-class teacher (optional)
- [ ] Class and section rename/merge/split utility
- [ ] Deactivate section if no students

### 3.3 Subject Management
- [x] Subject master: name, code, type (Theory/Practical/Activity/Language)
- [ ] Language subjects: First Language, Second Language, Third Language
- [ ] Elective vs compulsory subject flag
- [ ] Board curriculum tagging (CBSE/Tamil Nadu State Board/Both)
- [ ] Subject credit hours per week
- [x] Assign subjects to classes (class-subject mapping)
- [ ] Subject group/stream for Classes XI–XII (Science/Commerce/Arts/Vocational)
- [ ] Medium of instruction per subject (Tamil/English)

### 3.4 Syllabus & Lesson Plan Management
- [ ] Upload syllabus document (PDF) per subject per class per term
- [x] Structured syllabus builder: Unit → Chapter → Topic → Sub-topic
- [x] Lesson plan creation: topic, date, period, teaching method, learning objectives, resources
- [ ] Lesson plan approval workflow (HOD review)
- [x] Lesson completion tracking (teacher marks topic as done)
- [ ] Syllabus coverage % auto-calculated
- [ ] Lesson plan print as PDF
- [ ] Syllabus completion report per class/subject

### 3.5 Teacher Subject Allocation
- [x] Assign teachers to subjects for each class/section
- [ ] One subject can have multiple teachers across sections
- [ ] Teacher workload report: total periods per week
- [ ] Maximum workload limit per teacher (configurable)
- [ ] Alert if teacher is over-allocated
- [ ] Subject allocation history per academic year

### 3.6 Timetable Management
- [x] Define school periods: period number, start time, end time
- [ ] Define break/lunch periods
- [ ] Auto-generate timetable (constraint-based: no teacher clash, no subject clash)
- [x] Manual timetable entry/override
- [x] Section-wise timetable view
- [ ] Teacher-wise timetable view
- [ ] Conflict detection and highlight (teacher double-booked, room double-booked)
- [ ] Substitution management: mark absent teacher, auto-suggest available substitute
- [ ] Substitution register (log of all substitutions with date, period, original and substitute teacher)
- [ ] Timetable effective date (supports mid-year timetable change)
- [ ] Print timetable as PDF (class-wise and teacher-wise)
- [ ] Publish timetable to student/parent portal

### 3.7 Homework & Assignment Management
- [x] Teacher creates homework/assignment: subject, class/section, title, description, due date
- [ ] File attachment support (PDF, image, doc)
- [ ] Homework visible to students/parents on portal from creation date
- [ ] Mark assignment as submitted per student
- [ ] Mark assignment as evaluated with score/remarks
- [ ] Overdue assignment alert to student/parent
- [ ] Homework calendar view for students
- [ ] Homework completion report (% submitted per class)

### 3.8 NEP 2020 & Competency-Based Framework
- [ ] Competency master: subject-wise competencies per class
- [ ] Map competencies to topics/chapters
- [ ] Competency-based assessment entry (Achieved / Partially Achieved / Not Achieved)
- [ ] Student competency progress report
- [ ] 360-degree assessment entry (co-scholastic areas: arts, sports, values)
- [ ] Activity-based learning record per student

### 3.9 Notice Board & Circulars
- [ ] Create circular/notice: title, body, attachment (PDF/image), publish date, expiry date
- [ ] Target audience: All / Specific class / Specific section / Teachers only / Parents only
- [ ] Priority flag (Normal / Urgent)
- [ ] Push notification + SMS on new circular (optional per circular)
- [ ] Archive of all past circulars
- [ ] Student/parent portal notice board with read status
- [ ] Read receipt tracking per notice

---

## MODULE 4 — Attendance Management

### 4.1 Student Attendance — Daily
- [x] Date-wise attendance marking per class/section
- [x] One-click bulk present (mark all present, then manually absent specific students)
- [x] Attendance statuses: Present / Absent / Late / Half Day / On Leave / Holiday
- [ ] Reason capture for absence (optional)
- [ ] Late arrival time logging
- [ ] Early departure time logging
- [ ] Lock attendance after cutoff time (configurable, e.g., after 10 AM)
- [ ] Admin override to edit locked attendance with reason log

### 4.2 Student Attendance — Period-wise
- [x] Period-wise attendance toggle (enable/disable per school config)
- [x] Mark attendance separately for each period
- [x] Period attendance summary auto-aggregates to daily status
- [ ] Period-wise attendance report per subject per student

### 4.3 Biometric Device Integration
- [ ] ZKTeco device SDK integration
- [ ] Realtime device integration
- [ ] Suprema device integration
- [ ] Push/pull biometric punch data to attendance module
- [ ] Map biometric device ID to student/staff profile
- [ ] Auto-mark attendance on biometric punch-in
- [ ] Offline sync: store punches locally when internet unavailable, sync on reconnect
- [ ] Biometric device health status indicator in admin panel

### 4.4 RFID Integration
- [ ] RFID card number mapped to each student
- [ ] RFID reader entry/exit timestamp logging
- [ ] Auto-mark attendance on RFID entry scan
- [ ] Gate entry/exit report per student per day
- [ ] Unrecognised card alert to admin

### 4.5 Parent Notifications — Attendance
- [ ] Auto SMS to parent when student marked absent
- [ ] WhatsApp message to parent on absence
- [ ] Configurable message template (school customisable)
- [ ] Notification trigger time configurable (e.g., 9:30 AM)
- [ ] Late arrival SMS to parent
- [ ] Early departure SMS to parent
- [ ] Do-not-disturb (DND) filter handling for SMS

### 4.6 Attendance Shortage & Eligibility
- [x] Configure minimum attendance % for exam eligibility (default: 75%)
- [x] Real-time shortage alert when student crosses shortage threshold
- [x] Attendance shortage letter generation (PDF) to parent
- [ ] Condonation of attendance by principal with reason log
- [ ] Exam hall ticket block for students below threshold (configurable)

### 4.7 Leave Management (Student)
- [x] Parent submits leave application via portal
- [x] Leave types: Sick Leave, Personal Leave, Function Leave
- [x] Class teacher approval / rejection with remarks
- [x] Leave history per student
- [ ] Approved leave reflected as "On Leave" in attendance (not counted as absent for shortage)
- [ ] Medical certificate upload for sick leave

### 4.8 Staff Attendance
- [x] Staff daily attendance: Present / Absent / On Leave / Half Day
- [ ] Biometric punch-in/punch-out sync for staff
- [x] Manual attendance entry by admin
- [ ] Late arrival tracking for staff
- [ ] Staff attendance dashboard: today's present/absent summary
- [x] Monthly staff attendance register (printable)
- [ ] Staff attendance vs leave reconciliation
- [x] Leave calendar view per staff member

### 4.9 Reports & Registers
- [x] Daily attendance register per class/section (printable, official format)
- [x] Monthly attendance register (31-day grid format)
- [x] Student-wise attendance summary: total days, present, absent, leave, %
- [ ] Class-wise attendance summary with period-wise breakdown
- [ ] Chronic absentee report (students absent > N days, configurable threshold)
- [ ] Date-wise school strength report
- [ ] Teacher attendance report
- [x] Export all reports to PDF

---

## MODULE 5 — Examination & Results Management

### 5.1 Exam Configuration
- [x] Exam type master: Unit Test, Monthly Test, Half-Yearly, Annual, Practical, Project, Board Exam
- [x] Exam schedule creation: name, academic year, term, start date, end date
- [x] Subject-wise exam date and time slot
- [x] Exam applicable classes: select specific or all classes
- [x] Maximum marks, pass marks, and duration per subject per exam
- [ ] Grace marks configuration (optional, admin enable)
- [ ] Internal vs external exam flag

### 5.2 Grading Scheme
- [x] Marks-based grading (0–100 → percentage → rank)
- [x] Grade-based (A1, A2, B1, B2, C1, C2, D, E as per CBSE)
- [ ] GPA/CGPA scheme
- [x] Custom grade scale builder (school can define own grade ranges)
- [x] Separate grading scheme assignable per class
- [ ] Co-scholastic grading (A/B/C/D for activities, sports, values)

### 5.3 Question Paper Management
- [ ] Question paper template builder (school letterhead, format)
- [x] Question bank entry: subject, class, chapter, type (MCQ/Short/Long/Descriptive), marks, difficulty
- [ ] Manual question paper assembly from question bank
- [ ] Question paper upload as PDF
- [ ] Question paper access control (restricted until exam date)
- [ ] Question paper print management

### 5.4 Hall Ticket Generation
- [x] Hall ticket template with school branding
- [x] Auto-fill: student name, photo, class, section, roll no, exam centre, exam schedule
- [x] Subject-wise date/time printed on hall ticket
- [ ] QR code on hall ticket for verification
- [x] Bulk hall ticket generation and print (PDF)
- [ ] Hall ticket block for fee defaulters (configurable)
- [ ] Hall ticket block for attendance shortage students (configurable)

### 5.5 Marks Entry
- [x] Subject-wise marks entry by teacher or admin
- [x] Validation: marks ≤ maximum marks, marks ≥ 0
- [x] Absent/Withheld/Exempted status per student per subject
- [ ] Grace marks addition (tracked separately)
- [ ] Marks import via Excel template
- [ ] Marks lock after final submission (admin unlock with log)
- [ ] Marks entry progress indicator (% subjects entered per class)
- [ ] Marks recheck request workflow

### 5.6 Result Computation
- [x] Auto-calculate: total marks, percentage, grade, result status (Pass/Fail/Promoted/Detained)
- [x] Subject-wise pass/fail based on pass marks
- [ ] Best-of-N-subjects logic (CBSE Class X/XII type)
- [x] Rank calculation: class rank, section rank
- [ ] Cumulative marks across terms (with weightage config)
- [ ] CGPA calculation for CBSE schools
- [ ] Subject-wise highest, lowest, average marks computation
- [ ] Supplementary exam eligibility flag

### 5.7 Report Card Generation
- [x] Report card template (school logo, principal sign, class teacher sign area)
- [x] Subject-wise marks/grades display
- [ ] Co-scholastic area grades display
- [ ] Attendance summary on report card
- [ ] Class teacher remarks field (free text)
- [ ] Principal remarks field
- [ ] Graphical performance bar/radar chart on report card
- [ ] Term-wise and cumulative marks on same report card
- [x] Generate single student report card (PDF)
- [ ] Bulk generate all students of a class (batch PDF)
- [ ] Digital signature/stamp option
- [ ] Report card download from student/parent portal
- [x] Print report card with official formatting

### 5.8 Tabulation Register
- [x] Class-wise tabulation register: all students × all subjects marks
- [x] Pass/fail status per student per subject in tabulation
- [x] Rank column in tabulation register
- [x] Excel export of tabulation register
- [ ] PDF export of tabulation register (official format)

### 5.9 Online Exam Module
- [ ] Question types: MCQ (single/multiple correct), True/False, Fill in the Blank, Short Answer
- [ ] Online exam scheduling: start time, end time, duration
- [ ] Auto-submit when time expires
- [ ] Random question order per student (anti-cheating)
- [ ] MCQ auto-evaluation with instant result
- [ ] Short answer manual evaluation by teacher
- [ ] Student exam attempt log
- [ ] Negative marking configuration (optional)
- [ ] Online exam result published to student portal

### 5.10 Result Notifications
- [ ] Bulk SMS to parents with result summary (total marks, grade, rank)
- [ ] WhatsApp result notification to parents
- [ ] Email result notification with report card PDF attachment
- [ ] Configurable notification trigger (admin manually triggers after verification)

### 5.11 Reports
- [ ] Class-wise result summary: pass%, fail count, toppers
- [ ] Subject-wise performance report
- [ ] Student-wise result history across all exams
- [ ] Topper list per class (top 3/5/10 configurable)
- [ ] Failed student list with subjects failed
- [ ] Comparative analysis: class average vs school average
- [ ] Export all reports to PDF and Excel

---

## MODULE 6 — Fee Management & Accounts

### 6.1 Fee Structure Configuration
- [x] Fee category master: Tuition Fee, Admission Fee, Lab Fee, Sports Fee, Library Fee, Transport Fee, Hostel Fee, Exam Fee, Uniform, Books, Miscellaneous (custom heads)
- [x] Fee structure builder: assign fee heads + amounts per class
- [x] Academic year-wise fee structure (separate for each year)
- [ ] Category-wise fee variation (General/SC/ST different tuition)
- [ ] Individual student custom fee (override class structure)
- [x] Installment plan: define number of installments and due dates
- [ ] Monthly, quarterly, half-yearly, annual payment options per fee head
- [ ] New admission fee vs existing student fee differentiation
- [ ] GST applicability per fee head (Y/N, GST %, HSN code)

### 6.2 Fee Assignment
- [ ] Bulk assign fee structure to class (all students)
- [ ] Auto-assign fee on student admission/enrollment
- [ ] Sibling concession auto-apply on sibling linking
- [x] Scholarship concession auto-apply
- [ ] Fee revision: bulk update fee amount for a class mid-year
- [ ] Fee change history log per student

### 6.3 Fee Collection
- [x] Cash/Cheque/DD payment entry by cashier
- [ ] Online payment via Razorpay (UPI, Net Banking, Credit Card, Debit Card)
- [ ] Online payment via PayU (alternative gateway config)
- [ ] Payment success/failure webhook handling
- [x] Partial payment acceptance with remaining balance tracking
- [ ] Advance payment acceptance and carry-forward
- [ ] Record cheque details: number, bank, branch, date
- [ ] Cheque bounce handling: reverse payment, add bounce charge
- [x] Fee receipt auto-generation on payment (auto-incremented receipt number)
- [x] Fee receipt with school letterhead, stamp/sign area
- [ ] GST-compliant invoice with GSTIN, HSN code
- [ ] Duplicate receipt reprint
- [x] Cancel/reverse a payment with reason and admin approval

### 6.4 Fine & Late Fee
- [x] Fine/late fee rules configuration: per-day or flat after due date
- [x] Fine calculation auto-applies after due date
- [x] Fine waiver by admin with reason capture
- [ ] Fine displayed separately on receipt
- [ ] Fine collection report

### 6.5 Concession & Scholarship in Fee
- [x] Multiple concessions stackable per student (sibling + scholarship + staff ward)
- [ ] Staff ward fee concession scheme
- [x] Concession validity dates
- [x] Concession ledger per student (what was conceded, when, by whom)
- [ ] Concession report for management

### 6.6 Fee Reminders & Defaulter Management
- [ ] Automated reminder SMS on due date
- [ ] Automated reminder WhatsApp message on due date
- [ ] Automated email reminder with fee balance details
- [ ] Reminder schedule: 3 days before due, on due date, 7 days after, 15 days after
- [ ] Reminder schedule configurable by admin
- [x] Defaulter list: students with outstanding balance > 0
- [ ] Defaulter aging report: 0–30 days, 31–60 days, 61–90 days, 90+ days overdue
- [ ] Defaulter list export to Excel
- [ ] Defaulter letter generation (demand notice PDF) to parent
- [ ] Hold/block portal access for long-term defaulters (configurable)

### 6.7 Fee Ledger & Reports
- [x] Student-wise fee ledger: all transactions (charges, payments, balance)
- [x] Day book: all fee collections on a given date
- [x] Daily collection report by payment mode (cash/online/cheque)
- [ ] Monthly collection report with comparison to previous month
- [ ] Annual collection report: expected vs collected vs outstanding
- [ ] Class-wise fee collection summary
- [ ] Fee head-wise collection report (e.g., total transport fee collected)
- [ ] Outstanding balance report with contact details
- [ ] Cashier-wise collection report
- [ ] Bank reconciliation report
- [x] Export reports to PDF and Excel

### 6.8 Tally Export
- [ ] Configure Tally ledger mapping (fee head → Tally ledger name)
- [x] Export fee collection data in Tally XML import format
- [x] Export on-demand per date range
- [ ] GST details included in export

### 6.9 Parent Self-Service
- [ ] Parent portal: view fee statement (all dues and paid history)
- [ ] Online payment initiation from portal
- [ ] Download payment receipts from portal
- [ ] Outstanding balance widget on parent dashboard

---

## MODULE 7 — HR & Payroll Management

### 7.1 Employee Master
- [x] Employee unique ID (auto-generated)
- [x] Personal info: full name, DOB, gender, blood group, nationality, religion, category
- [x] Aadhaar number, PAN number
- [x] Contact: mobile, personal email, emergency contact
- [x] Residential and permanent address
- [ ] Passport-size photo upload
- [x] Official email address
- [x] Employee type: Teaching / Non-Teaching / Contract / Part-Time
- [x] Date of joining
- [ ] Qualification details: degree, subject, university, year of passing, grade (multi-entry)
- [ ] Previous experience: organisation, role, from-to dates, reason for leaving (multi-entry)
- [ ] Certification and training records
- [ ] Document uploads: Aadhaar, PAN, Degree Certificate, Experience Letter, Police Verification
- [x] Bank account details: account number, IFSC, bank, branch (for salary transfer)
- [ ] PF account number, ESI number (if applicable)

### 7.2 Designation & Department
- [x] Department master: Academic, Administration, Accounts, Transport, Hostel, Library, Sports, IT
- [x] Designation master: Principal, Vice Principal, HOD, Senior Teacher, Teacher, Lab Assistant, Clerk, etc.
- [ ] Grade/Pay band assignment per designation
- [ ] Reporting hierarchy (who reports to whom)
- [ ] Role-based access assignment per designation

### 7.3 Staff ID Card
- [ ] Staff ID card template (separate design from student card)
- [ ] Auto-fill: photo, name, designation, department, employee ID, mobile, blood group, validity
- [ ] Barcode/QR on staff card
- [ ] Bulk print staff ID cards
- [ ] PDF export

### 7.4 Leave Management
- [x] Leave type master: Casual Leave (CL), Earned Leave (EL), Sick Leave (SL), Maternity Leave (ML), Paternity Leave, Compensatory Off, Loss of Pay (LOP)
- [x] Leave entitlement per leave type per year (configurable per designation)
- [ ] Leave balance tracker per employee
- [x] Leave application by employee (portal/app)
- [x] Leave approval workflow: HOD → Principal
- [x] Leave rejection with reason
- [ ] Leave cancellation request (before approval or before leave date)
- [ ] Leave history per employee
- [x] Leave calendar view (team calendar showing who is on leave)
- [ ] Half-day leave support
- [ ] Leave encashment calculation (EL)
- [ ] Leave balance carry-forward rules
- [ ] Leave report: balance, availed, pending per employee

### 7.5 Payroll Processing
- [x] Pay structure builder: Basic, DA, HRA, TA, Special Allowance, Medical Allowance (configurable heads)
- [x] Deduction heads: PF Employee, PF Employer, ESI Employee, ESI Employer, PT, TDS, Loan EMI, Advance Recovery
- [x] Employee-wise salary structure assignment
- [x] Payroll run per month (process all employees)
- [x] Auto-calculate gross, deductions, net pay
- [ ] Attendance-linked pay: auto-deduct for LOP days
- [ ] Overtime hours entry and calculation (if applicable)
- [ ] Arrears and bonus addition
- [ ] Advance salary and recovery tracking
- [ ] Loan management: amount, EMI, outstanding (deducted from salary)
- [ ] Salary hold for specific employee
- [ ] Payroll approval workflow (accounts → principal)
- [ ] Payroll lock after approval (prevent modification)

### 7.6 Statutory Computations
- [ ] PF computation: 12% employee + 12% employer on basic (configurable ceiling)
- [ ] ESI computation: 0.75% employee + 3.25% employer on gross (applicable ceiling)
- [ ] Professional Tax (PT): slab-based computation per state (Tamil Nadu PT slabs configured)
- [ ] TDS computation: Form 16 relevant deductions, investment declarations
- [ ] PT challan generation
- [ ] PF challan (ECR format) generation
- [ ] ESI challan generation

### 7.7 Payslip Generation
- [x] Payslip template with school logo and letterhead
- [x] Payslip fields: all earnings, deductions, gross, net, leave summary, bank account
- [x] Individual payslip PDF generation
- [ ] Bulk payslip generation for all employees
- [ ] Payslip delivery: email to employee, portal download
- [ ] Password-protected payslip PDF (employee DOB or PAN as password)

### 7.8 Bank Transfer File
- [x] Generate bank transfer advice file (NEFT/RTGS format)
- [ ] Bank-specific format: SBI, HDFC, Axis, ICICI (configurable)
- [x] Excel/CSV export of salary transfer file

### 7.9 Appraisal & Increment
- [ ] Annual appraisal form creation (configurable KPIs)
- [ ] Self-appraisal by employee
- [ ] HOD/Principal appraisal entry
- [ ] Appraisal rating: Outstanding / Very Good / Good / Average / Below Average
- [ ] Increment processing linked to appraisal rating
- [ ] Increment effective date and revised CTC
- [ ] Increment letter generation (PDF)

### 7.10 Service Documents
- [x] Appointment letter template and generation
- [x] Experience/service certificate generation
- [ ] Relieving letter generation
- [ ] No-objection certificate (NOC) generation
- [x] All documents generated as PDF with school letterhead

### 7.11 Payroll Reports
- [x] Monthly salary register (all employees)
- [ ] Department-wise salary summary
- [ ] PF/ESI/PT statement per month
- [ ] Annual TDS summary (Form 16 data)
- [ ] Increment history report
- [ ] Leave encashment report
- [ ] Export all reports to PDF and Excel

---

## MODULE 8 — Library Management

### 8.1 Book Catalogue
- [x] Book master: title, author(s), publisher, publication year, edition, ISBN, language
- [ ] ISBN-based auto-fill via Open Library API lookup
- [x] Book category/genre: Fiction, Non-Fiction, Science, Mathematics, Reference, Textbook, Periodical, etc.
- [x] Subject and class-level tagging for textbooks
- [x] Total copies, available copies, issued copies count
- [x] Book location: rack number, shelf number, row
- [x] Book acquisition: purchase date, price, vendor, invoice number
- [x] Book condition: Good / Fair / Damaged / Lost
- [ ] Book cover image upload
- [ ] Deaccession (write-off) a book with reason
- [ ] Bulk book import via Excel

### 8.2 Barcode / QR Management
- [ ] Auto-generate barcode/QR per book copy
- [ ] Print barcode labels (single or bulk)
- [ ] Barcode scan to look up book/member in issue-return screen
- [ ] Mobile camera-based QR scan support

### 8.3 Member Management
- [x] Student members auto-synced from SIS (no duplicate entry)
- [ ] Staff members auto-synced from HR module
- [x] Member library card generation
- [ ] Active/suspended/graduated member status
- [ ] Per-member borrowing limit configurable (e.g., student: 2 books, teacher: 5 books)

### 8.4 Book Issue & Return
- [x] Issue book to member: scan barcode or search book + scan member card
- [x] Issue date auto-stamp
- [x] Due date auto-calculate (configurable loan period per member type)
- [x] Return scan: book barcode lookup, auto-calculate fine if overdue
- [ ] Fine collection integration with Fee module or separate library fine account
- [ ] Issue/return receipt (printable or on-screen)
- [ ] Renewal of borrowed book (extend due date, max 1 renewal)
- [ ] Lost book handling: mark as lost, charge replacement cost

### 8.5 Reservation & Hold Queue
- [x] Member can reserve a book currently on issue
- [x] Reservation queue (first-come-first-served)
- [ ] Notification to reserver when book is returned (SMS/portal notification)
- [x] Reservation expiry after N days (configurable)

### 8.6 Fine Management
- [ ] Fine rate per day configuration (member-type specific)
- [x] Auto-calculate fine on return
- [x] Fine waiver by librarian with reason
- [ ] Fine collection record (cash/portal)
- [ ] Fine outstanding per member
- [ ] Fine collection report

### 8.7 Overdue Reminders
- [ ] Auto SMS on due date to member
- [ ] Auto SMS 3 days after due date (escalation)
- [ ] Auto email reminder
- [ ] Portal notification for overdue books

### 8.8 Digital Resources
- [x] Digital resource catalogue: e-books, PDFs, reference links
- [x] Upload e-book/PDF files (stored on server)
- [x] External URL linking (YouTube, open-access resources)
- [ ] Access control: available to all / specific classes
- [x] Download count tracking

### 8.9 Reports
- [ ] Currently issued books list (who has what)
- [ ] Overdue books with member contact details
- [ ] Most borrowed books (popularity ranking)
- [ ] Member-wise borrowing history
- [ ] Fine defaulter list
- [x] Stock register: all books with copies and availability
- [ ] Annual stock audit report (physical vs system count)
- [ ] Acquisition report (books added in date range)
- [ ] Deaccession register
- [x] Export stock register to Excel

---

## MODULE 9 — Transport Management

### 9.1 Route & Stop Configuration
- [x] Route master: route name, route number, description
- [x] Stop master per route: stop name, sequence order, pickup time, drop time
- [ ] Distance from school per stop
- [ ] Google Maps embed for route visualisation
- [ ] Route deactivation when not in use

### 9.2 Vehicle Fleet Management
- [x] Vehicle master: registration number, type (Bus/Van/Auto), make, model, year
- [x] Seating capacity per vehicle
- [x] Fitness certificate expiry tracking
- [x] Insurance expiry tracking
- [x] Pollution certificate expiry tracking
- [x] Road tax expiry tracking
- [x] Permit expiry tracking
- [x] Document expiry alerts (30/15/7 days before)
- [x] Vehicle assign to route

### 9.3 Driver & Attendant Management
- [x] Driver master: name, mobile, license number, license type, license expiry, Aadhaar, photo
- [ ] Attendant master: name, mobile, Aadhaar, photo
- [x] Assign driver + attendant to vehicle/route
- [ ] License expiry alert
- [ ] Police verification status tracking
- [ ] Driver contact visible to admin and parent (portal)

### 9.4 Student Route Assignment
- [x] Assign student to route and stop from student profile
- [ ] Transport fee auto-link to student fee structure based on stop/distance
- [x] Stop-wise student list (roster)
- [x] Route-wise student count and seat occupancy
- [ ] Seat availability check before new assignment
- [ ] Multiple students per stop support

### 9.5 GPS Live Tracking
- [ ] GPS device integration (standard GPRS tracker with API/webhook)
- [ ] Live vehicle location on Google Maps in admin dashboard
- [ ] Live location visible to parent via portal/app (within school hours)
- [ ] Estimated arrival time at each stop
- [ ] Geofence: alert when bus enters/exits school premises
- [ ] Geofence: alert when bus deviates from route
- [ ] GPS tracking history (replay route for any date)

### 9.6 Bus Attendance & Parent Alerts
- [ ] Bus attendant marks student boarded/not-boarded (via app/portal)
- [ ] Automatic SMS to parent when student boards the bus (morning)
- [ ] Automatic SMS to parent when student alights (evening drop)
- [ ] RFID card scan on bus entry/exit (if RFID enabled)
- [ ] Absent-on-bus alert: student not boarded by expected time
- [ ] Bus attendance register

### 9.7 Vehicle Maintenance & Fuel
- [x] Maintenance log: date, type (scheduled/breakdown), work done, vendor, cost
- [ ] Next service reminder (by date or km)
- [x] Fuel log: date, quantity (litres), cost, odometer reading
- [x] Fuel efficiency tracking (km per litre)
- [ ] Maintenance expense report
- [ ] Fuel expense report per vehicle

### 9.8 Emergency & Safety
- [ ] SOS alert button for driver/attendant (triggers SMS to principal + admin + parent)
- [ ] Emergency contact list for each route
- [ ] Breakdown reporting by driver (portal/app)
- [ ] Breakdown notification to admin + affected parents
- [ ] Accident/incident log

### 9.9 Reports
- [x] Route-wise student roster (stop-wise pickup list)
- [ ] Vehicle utilisation report (seats filled vs capacity)
- [x] Document expiry calendar
- [ ] Fuel expense monthly summary
- [ ] Maintenance cost per vehicle
- [ ] Bus attendance report
- [ ] Parent alert delivery status report
- [ ] Export all reports to PDF and Excel

---

## MODULE 10 — Hostel Management

### 10.1 Hostel Infrastructure Setup
- [x] Hostel block/building master: name, type (Boys/Girls)
- [ ] Floor master per block
- [x] Room master: room number, floor, block, type (Single/Double/Triple/Dormitory), capacity
- [ ] Bed master per room: bed ID/number
- [ ] Amenities per room: AC/Fan, attached/common bathroom, locker
- [x] Total capacity, occupied, available beds dashboard

### 10.2 Student Allotment
- [x] Allot bed to student: select block, room, bed
- [x] Allotment from-to date
- [ ] Transfer student to different room/bed
- [ ] Vacate student from hostel (with reason: passed out, withdrawal, disciplinary)
- [ ] Student hostel ID card / room label
- [x] Room allotment letter generation (PDF)

### 10.3 Hostel Fee
- [x] Hostel fee structure configuration (separate from tuition fee)
- [x] Room type-wise fee differentiation (AC vs non-AC, single vs double)
- [ ] Mess fee inclusion/exclusion option
- [ ] Fee auto-link to main Fee Management module
- [ ] Hostel fee receipt from main fee collection

### 10.4 Mess Management
- [x] Weekly/monthly mess menu creation (breakfast, lunch, snacks, dinner)
- [x] Mess menu publish to student portal
- [ ] Daily mess attendance (present/absent for each meal)
- [ ] Mess rebate for absentees (if configured)
- [ ] Mess feedback from students (rating per meal)
- [ ] Mess expense tracking (ingredients, vendor)

### 10.5 Warden & Staff
- [ ] Assign warden(s) to hostel block
- [ ] Warden duty roster
- [ ] Night duty staff assignment
- [ ] Warden emergency contact visible to parents

### 10.6 Gate Pass & Outing Management
- [x] Student outing request: destination, purpose, date/time out, expected return
- [ ] Parent approval required (notification + approval from parent portal)
- [x] Warden approval workflow
- [ ] Gate pass QR code generation
- [x] Actual exit and return time logging
- [ ] Late return alert to warden and parent
- [x] Outing register

### 10.7 Visitor Management (Hostel)
- [x] Visitor registration at hostel gate: visitor name, student name, relation, ID proof, purpose
- [ ] Photo capture of visitor
- [ ] Allowed visiting hours enforcement
- [ ] Visitor pass issuance with time-limited QR
- [x] Visitor exit log
- [x] Visitor history per student

### 10.8 Disciplinary Records
- [x] Log hostel disciplinary incident per student
- [ ] Warning issuance and record
- [ ] Repeat offence tracking
- [ ] Parent notification on hostel discipline issue

### 10.9 Maintenance & Complaints
- [x] Student submits maintenance complaint (room, facility, electrical, plumbing)
- [ ] Complaint assigned to staff/vendor
- [x] Status: Open → In Progress → Resolved
- [x] Resolution notes and date
- [ ] Complaint history per room

### 10.10 Reports
- [ ] Current occupancy report
- [ ] Room-wise student list
- [ ] Hostel fee outstanding list
- [x] Gate pass register
- [x] Visitor log report
- [ ] Mess attendance summary
- [ ] Maintenance complaint status report
- [ ] Export all reports to PDF and Excel

---

## MODULE 11 — Parent & Student Portal

### 11.1 Authentication
- [ ] Secure login: email/mobile + password
- [ ] OTP-based login (mobile number verification)
- [ ] Password reset via OTP on registered mobile/email
- [ ] Session timeout configurable
- [ ] Remember me (optional)
- [ ] Failed login lockout after N attempts
- [ ] Role detection on login (auto-route to correct dashboard)

### 11.2 Parent Dashboard
- [ ] Today's attendance status for each child
- [ ] Upcoming fee dues with amounts
- [ ] Latest exam results / recent marks
- [ ] Recent circulars / notices (unread count badge)
- [ ] Upcoming exam schedule
- [ ] Upcoming school events / holidays
- [ ] Child's timetable for the week
- [ ] Library books currently borrowed (if any overdue)
- [ ] Bus live tracking quick link
- [ ] Real-time notification bell

### 11.3 Student Dashboard
- [ ] Today's timetable
- [ ] Pending/upcoming homework
- [ ] Recent marks entry
- [ ] Attendance summary (current month)
- [ ] Library status (books issued, due dates)
- [ ] Notice board
- [ ] Upcoming exams
- [ ] Online exam access (if scheduled)
- [ ] Digital study materials
- [ ] Notification bell

### 11.4 Attendance Section (Parent/Student)
- [ ] Month-wise attendance calendar view (colour-coded: green/red/grey)
- [ ] Date-wise status detail (present/absent/late/leave)
- [ ] Period-wise attendance (if enabled)
- [ ] Attendance percentage display
- [ ] Leave application form

### 11.5 Fee Section (Parent)
- [ ] Complete fee statement: all dues, paid, balance
- [ ] Installment-wise due dates and status
- [ ] Pay now button (Razorpay/PayU integration)
- [ ] Payment history with receipt download
- [ ] Pending fine details
- [ ] GST invoice download

### 11.6 Academics Section
- [ ] Syllabus view per subject (PDF)
- [ ] Lesson plan (if published by teacher)
- [ ] Homework list with due dates and attachments
- [ ] Assignment submission (file upload)
- [ ] Study materials / digital resources
- [ ] Online exam access

### 11.7 Examination & Results Section
- [ ] Exam schedule calendar
- [ ] Hall ticket download
- [ ] Subject-wise marks per exam
- [ ] Report card download (PDF)
- [ ] Progress chart (graphical performance over terms)

### 11.8 Communication Section
- [ ] Notice board with all circulars (searchable, filterable)
- [ ] Announcement alerts
- [ ] Parent-teacher messaging (direct message, in-portal)
- [ ] Message history (inbox/sent)
- [ ] Event calendar

### 11.9 Profile Management
- [ ] View student profile (read-only for most fields)
- [ ] Parent can edit contact mobile, email, address
- [ ] Change portal password
- [ ] Notification preferences (SMS/WhatsApp/email on/off per category)
- [ ] Multiple children support (switch between children from same login)

---

## MODULE 12 — Communication & Notifications

### 12.1 SMS Gateway
- [ ] MSG91 API integration
- [ ] Textlocal API integration
- [ ] Twilio API integration (fallback)
- [ ] SMS gateway selection and API key configuration in admin
- [ ] SMS credit balance display in admin panel
- [ ] DND filtering (automatically exclude DND numbers)
- [ ] Sender ID configuration (school name as sender)
- [ ] SMS delivery report per message

### 12.2 WhatsApp Business API
- [ ] WhatsApp Business API integration (360Dialog / Meta Cloud API)
- [ ] Template message creation and approval management
- [ ] Template categories: Attendance, Fee Reminder, Result, Circular, General
- [ ] Opt-in management (parent consent for WhatsApp messages)
- [ ] WhatsApp message delivery status tracking
- [ ] Fallback to SMS if WhatsApp delivery fails

### 12.3 Email System
- [ ] SMTP configuration (school's email server or Gmail/Outlook SMTP)
- [ ] Bulk email composer with HTML template builder (WYSIWYG)
- [ ] Email subject, body, attachments
- [ ] Target audience: All parents, All staff, Specific class, Custom list
- [ ] Schedule email for future delivery
- [ ] Email delivery status (sent/failed)
- [ ] Email open/click tracking (if supported by SMTP service)
- [ ] Unsubscribe handling

### 12.4 Push Notifications
- [ ] Firebase Cloud Messaging (FCM) integration for mobile app push notifications
- [ ] Push notification triggers: new circular, exam result published, fee due
- [ ] Notification history (last 30 days) in portal notification centre
- [ ] Mark as read / mark all as read

### 12.5 Auto-Trigger Notifications
- [ ] Absence alert: SMS/WhatsApp to parent when student absent (morning)
- [ ] Fee due reminder: automated on due date (and before/after as configured)
- [ ] Exam result published: SMS/WhatsApp/email to parent
- [ ] New circular: push notification + portal bell
- [ ] Birthday wish to student (on DOB, morning SMS/WhatsApp)
- [ ] Staff birthday notification (internal)
- [ ] TC ready notification to parent
- [ ] Document expiry reminder (biometric, vehicle fitness, etc.)
- [ ] Low attendance alert (when student crosses shortage threshold)

### 12.6 Bulk Messaging
- [ ] Compose bulk SMS: select recipients (class/section/all/custom group), message (max 160 chars, auto-split for longer), schedule
- [ ] Compose bulk WhatsApp: select template, variable fill, recipients
- [ ] Compose bulk email (as above)
- [ ] Message preview before send
- [ ] Recipient count display before send
- [ ] Send confirmation + delivery report

### 12.7 Internal Staff Communication
- [ ] Internal message/notice to specific staff or all staff
- [ ] Staff notice board (separate from parent-facing board)
- [ ] Announcement with read receipt tracking

### 12.8 Communication Logs & Reports
- [ ] Full message log: date, type (SMS/WA/email), recipient, content, status
- [ ] Failed delivery report (retry option)
- [ ] SMS usage report (messages sent per month vs credits used)
- [ ] Export logs to Excel

---

## MODULE 13 — Reports & Analytics Dashboard

### 13.1 Executive Dashboard
- [ ] Current student strength (total, class-wise, gender-wise)
- [ ] Today's attendance %: school-wide, class-wise drill-down
- [ ] Fee collection today (cash + online)
- [ ] Fee outstanding total (all pending dues)
- [ ] New admissions this month / this year
- [ ] Staff present today count
- [ ] Quick links to frequently used modules
- [ ] Date/time display with academic year indicator

### 13.2 Admission Analytics
- [ ] Enquiry funnel (enquiries → applications → admitted)
- [ ] Source-wise enquiry chart
- [ ] Monthly admission trend (bar chart)
- [ ] Class-wise seat fill ratio

### 13.3 Attendance Analytics
- [ ] School-wide attendance trend (line chart: last 30 days)
- [ ] Class-wise attendance heatmap
- [ ] Chronic absentee count alert
- [ ] Period-wise attendance analysis (which period has most absences)
- [ ] Month-wise staff attendance summary

### 13.4 Academic Analytics
- [ ] Subject-wise average marks per class (bar chart)
- [ ] Exam performance comparison: Term 1 vs Term 2 vs Annual
- [ ] Class-wise pass % per exam
- [ ] Top performers list (configurable: top 10)
- [ ] Subject failure rate report (subjects with highest failure %)

### 13.5 Fee Analytics
- [ ] Collection vs outstanding live tracker (donut chart)
- [ ] Monthly collection trend chart
- [ ] Payment mode split (cash vs online vs cheque)
- [ ] Class-wise outstanding balances
- [ ] Top defaulters list (highest outstanding amount)

### 13.6 Custom Report Builder
- [ ] Drag-and-drop field selector: choose module, choose fields
- [ ] Filter conditions (where clause): field, operator, value
- [ ] Sort order configuration
- [ ] Save custom report as named template
- [ ] Run saved report
- [ ] Export custom report to PDF / Excel / CSV

### 13.7 Scheduled Report Delivery
- [ ] Configure scheduled reports: report name, frequency (daily/weekly/monthly), recipients (email list)
- [ ] Auto-generate and email report on schedule
- [ ] Schedule using shared hosting cron job (Laravel scheduler via cron)

### 13.8 Standard Registers & Official Reports
- [ ] General Register (admission register): all students in chronological admission order
- [ ] Attendance register (monthly, all formats)
- [ ] Caution deposit register
- [ ] TC register
- [ ] Fee collection register
- [ ] All standard registers printable in official government-prescribed formats

---

## MODULE 14 — Inventory & Store Management

### 14.1 Item Master
- [ ] Item master: item name, code, unit (pcs/kg/litre/set), category
- [ ] Item categories: Stationery, Lab Equipment, Sports Equipment, Furniture, Electrical, Uniform, Books, Cleaning Supplies, IT Equipment
- [ ] Minimum stock level (reorder point) per item
- [ ] Current stock quantity auto-maintained
- [ ] Item description and photo upload
- [ ] Discontinued item flag

### 14.2 Vendor Management
- [ ] Vendor master: name, contact person, mobile, email, address, GSTIN
- [ ] Item-vendor mapping (which vendor supplies which items)
- [ ] Vendor rating/notes
- [ ] Vendor payment terms

### 14.3 Purchase Management
- [ ] Purchase Requisition (PR): department, items, quantity, reason
- [ ] PR approval workflow (HOD → Principal → Accounts)
- [ ] Purchase Order (PO) generation from approved PR
- [ ] PO PDF generation with school letterhead
- [ ] Send PO to vendor via email
- [ ] Goods Receipt Note (GRN): receive items against PO, quantity check
- [ ] Partial delivery handling (partial GRN against PO)
- [ ] Purchase bill/invoice entry: vendor invoice number, date, amount
- [ ] GST input credit tracking on purchases
- [ ] Purchase expense against budget head

### 14.4 Stock Issuance
- [ ] Issue items to department / class / student
- [ ] Issue with quantity, date, issued-to person, purpose
- [ ] Stock auto-deducted on issuance
- [ ] Return of issued items (partial return supported)
- [ ] Non-returnable vs returnable item classification
- [ ] Issue register (log of all issuances)

### 14.5 Uniform & Book Store
- [ ] Uniform catalogue: item, size, price
- [ ] Book store catalogue: title, class, price
- [ ] Sale to student: linked to student account, amount added to fee due or collected directly
- [ ] Stock deduction on sale
- [ ] Sales register

### 14.6 Alerts & Audit
- [ ] Low stock alert: email/portal notification to store in-charge when item reaches reorder level
- [ ] Expiry tracking for consumables (chemicals, medicines in dispensary)
- [ ] Annual physical stock audit: enter actual count vs system count
- [ ] Discrepancy report (system qty vs physical qty)
- [ ] Write-off/adjustment with reason

### 14.7 Reports
- [ ] Stock ledger per item (all movements: purchase, issue, return)
- [ ] Current stock report (all items with stock level)
- [ ] Low stock report
- [ ] Purchase report (by date range, vendor, category)
- [ ] Issuance report (by department, date range)
- [ ] Vendor-wise purchase summary
- [ ] Budget utilisation report per department
- [ ] Export all reports to PDF and Excel

---

## MODULE 15 — Event & Calendar Management

### 15.1 Event Creation & Management
- [ ] Create event: name, type, date, time (start/end), venue, organiser/in-charge
- [ ] Event types: Annual Day, Sports Day, Exam, PTM, Holiday, Cultural, Religious, Field Trip, Other
- [ ] Target audience: All / Specific class / Staff only
- [ ] Event description (rich text)
- [ ] Event cover image upload
- [ ] Attachment upload (event notice PDF, itinerary)
- [ ] Publish/unpublish event (draft mode)
- [ ] Recurring event support (e.g., weekly assembly)

### 15.2 Calendar Views
- [ ] Monthly calendar view (admin and parent portal)
- [ ] Weekly view
- [ ] List/agenda view
- [ ] Colour-coded event types
- [ ] Holiday highlighting
- [ ] Exam dates overlaid on calendar
- [ ] Homework due dates on student calendar

### 15.3 RSVP & Attendance
- [ ] RSVP option for parents (Coming / Not Coming) for PTM and open events
- [ ] RSVP count dashboard for event organiser
- [ ] Attendance marking on event day (present/absent)
- [ ] Event attendance report

### 15.4 Photo Gallery
- [ ] Photo gallery per event
- [ ] Admin/teacher uploads event photos (bulk upload)
- [ ] Gallery visible on parent/student portal
- [ ] Album cover image selection
- [ ] Photo download by parents
- [ ] Image compression before upload (shared hosting disk conservation)

### 15.5 Birthday Management
- [ ] Auto-detect student and staff birthdays each day
- [ ] Birthday announcement to class (notice board or portal notification)
- [ ] Birthday SMS/WhatsApp wish to student/parent
- [ ] Monthly birthday list report

### 15.6 Notifications & Sync
- [ ] Push notification + SMS/email on event creation for target audience
- [ ] Reminder notification 1 day before event
- [ ] iCal (.ics) export for Google Calendar / Outlook import
- [ ] Public school website calendar feed (JSON/iCal endpoint, optional)

---

## MODULE 16 — Visitor & Gate Pass Management

### 16.1 Visitor Registration
- [ ] Visitor walk-in entry: name, mobile, ID type (Aadhaar/DL/PAN/Other), ID number, photo capture (webcam/upload)
- [ ] Whom to meet: staff name selection
- [ ] Purpose of visit: free text + category (Meeting/Delivery/Parent/Vendor/Official/Other)
- [ ] Vehicle number (if applicable)
- [ ] Visitor badge/pass printing
- [ ] QR-code gate pass (printable or mobile display)
- [ ] Gate pass expiry time (configurable e.g., 2 hours)

### 16.2 Notification to Host
- [ ] Auto SMS to staff member when visitor arrives for them
- [ ] In-portal notification to staff
- [ ] Staff can approve or reject visit from portal

### 16.3 Visitor Entry & Exit Log
- [ ] Entry timestamp auto-recorded on registration
- [ ] Exit timestamp recorded by security on departure
- [ ] Duration of visit auto-calculated
- [ ] Visitor checkout kiosk (security marks exit)
- [ ] Overstay alert (visitor still inside after gate pass expiry)

### 16.4 Student Outpass
- [ ] Student outpass request: student name, class, parent name, destination, purpose, out time, expected return
- [ ] Class teacher approval from portal
- [ ] Parent consent verification (OTP or portal acknowledgement)
- [ ] Outpass QR code for gate security
- [ ] Return time logging on re-entry
- [ ] Outpass register

### 16.5 Regular Entrants
- [ ] Regular driver/vendor/contractor daily entry log (pre-registered, faster check-in)
- [ ] Pre-register expected visitors (school events, interviews)
- [ ] Pre-registration confirmation SMS to visitor

### 16.6 Blacklist Management
- [ ] Blacklist a visitor: name, ID, reason, blacklisted by, date
- [ ] Auto-alert to security when blacklisted person attempts entry
- [ ] Blacklist override by admin only

### 16.7 Reports
- [ ] Daily visitor log (all visitors of the day)
- [ ] Weekly/monthly visitor summary
- [ ] Visitor frequency report (frequent visitors)
- [ ] Outpass register
- [ ] Overstay incidents report
- [ ] Export all reports to PDF and Excel

---

## MODULE 17 — Online Learning / LMS

### 17.1 Course Builder
- [ ] Create course: name, subject, class, description, thumbnail image, status (draft/published)
- [ ] Course structure: Units → Chapters → Lessons (tree builder)
- [ ] Drag-and-drop reorder of units/chapters/lessons
- [ ] Lesson types: Video, PDF/Document, Text, Quiz, Assignment

### 17.2 Content Upload & Embedding
- [ ] Video upload (stored on server, shared-hosting-safe: compressed MP4 ≤100MB, or link-only)
- [ ] YouTube video embed (URL-based, no upload needed — preferred for shared hosting)
- [ ] Google Drive file embed/link
- [ ] PDF/document upload per lesson
- [ ] Text/HTML content editor (WYSIWYG) per lesson
- [ ] Audio file upload for language lessons
- [ ] Image gallery per lesson

### 17.3 Live Class Integration
- [ ] Schedule live class: subject, class/section, date, time, duration, platform
- [ ] Zoom meeting link generation/embed
- [ ] Google Meet link creation
- [ ] Microsoft Teams link support
- [ ] Live class visible in student portal calendar
- [ ] SMS/push notification to students when live class is starting (15 min before)
- [ ] Recording link update after class (teacher adds recording URL)

### 17.4 Assignments & Submissions
- [ ] Assignment creation from LMS: title, instructions, due date, marks, attachment
- [ ] Student file submission from portal (PDF, image, doc)
- [ ] Submission timestamp record
- [ ] Late submission flag
- [ ] Teacher evaluation: score, feedback text, return to student
- [ ] Assignment submission report (submitted vs total)

### 17.5 Quiz Module
- [ ] Quiz builder: title, subject, duration, marks per question, negative marking option
- [ ] Question types: MCQ (single), MCQ (multiple), True/False, Fill in the Blank
- [ ] Question bank reuse (pull from examination question bank)
- [ ] Random question selection from pool
- [ ] Schedule quiz: available from/to date-time
- [ ] Auto-submit on timer expiry
- [ ] MCQ auto-evaluation + instant score display
- [ ] Quiz attempt history per student
- [ ] Quiz report: class-wise score distribution

### 17.6 Student Progress Tracking
- [ ] Lesson completion tracking (student marks lesson as done)
- [ ] Course progress % per student (lessons completed / total)
- [ ] Quiz scores and attempts history
- [ ] Assignment submission and score history
- [ ] Teacher dashboard: class-wide course progress
- [ ] Inactive student alert (not accessed LMS in X days)

### 17.7 Discussion Forum
- [ ] Forum per course/subject
- [ ] Student posts question / replies to thread
- [ ] Teacher posts announcements in forum
- [ ] Teacher marks reply as answered
- [ ] Notification on new reply to own thread
- [ ] Forum moderation: teacher can hide inappropriate posts

### 17.8 Digital Study Material Library
- [ ] Central library of study materials (separate from course lessons)
- [ ] Categorised by subject, class, topic
- [ ] Accessible without enrollment in a course
- [ ] Teacher uploads; admin approves
- [ ] Download count tracking

### 17.9 Certificate of Completion
- [ ] Certificate template designer (school logo, student name, course name, date)
- [ ] Auto-generate certificate when student completes 100% of course
- [ ] Downloadable PDF certificate
- [ ] Certificate verification QR code (online verification link)

---

## MODULE 18 — Alumni Management

### 18.1 Alumni Registration
- [ ] Alumni self-registration form (public link)
- [ ] Fields: full name, admission no. (for verification), batch/graduation year, class passed out, mobile, email
- [ ] Admin-initiated alumni registration from student records (on TC issuance)
- [ ] Alumni profile verification by admin
- [ ] Alumni unique ID generation

### 18.2 Alumni Profile
- [ ] Academic history at school (auto-pulled from SIS)
- [ ] Current occupation / profession
- [ ] Current employer / company name
- [ ] Job title / designation
- [ ] Location (city, country)
- [ ] Higher education details: college, degree, year
- [ ] LinkedIn profile URL
- [ ] Profile photo update
- [ ] Privacy settings (what to show in public alumni directory)

### 18.3 Alumni Directory
- [ ] Searchable alumni directory (by name, batch, profession, location)
- [ ] Filter by graduation year, stream, city, country
- [ ] View profile card (contact details shown only if alumni has consented)
- [ ] Access control: alumni can view other alumni profiles (login required)

### 18.4 Achievement Announcements
- [ ] Admin posts alumni achievement: name, achievement type (Award/Job/Sports/Research/Other), description, date
- [ ] Publish achievement on alumni portal
- [ ] Share achievement notification to all alumni (email/SMS)
- [ ] Featured alumni section on alumni portal

### 18.5 Alumni Events
- [ ] Create alumni event: reunion, webinar, networking meet
- [ ] Event invite to all alumni or specific batch
- [ ] RSVP tracking
- [ ] Event attendance log
- [ ] Photo gallery per alumni event

### 18.6 Donations & Contributions
- [ ] Donation campaign creation: cause, target amount, deadline
- [ ] Online donation via Razorpay (alumni login → pay)
- [ ] Donation receipt generation (80G format if applicable)
- [ ] Donation tracker (amount raised vs target)
- [ ] Donor recognition list (public or private)

### 18.7 Newsletters & Communication
- [ ] Alumni newsletter composition (email)
- [ ] Batch-wise newsletter targeting (e.g., 2010 batch, 2015 batch)
- [ ] Bulk SMS to alumni
- [ ] Send announcement to all alumni
- [ ] Alumni communication log

### 18.8 Reports
- [ ] Total alumni count by batch/year
- [ ] Alumni engagement report (logins, RSVPs)
- [ ] Donation summary
- [ ] Missing/incomplete profile list
- [ ] Export alumni directory to Excel

---

## MODULE 20 — System Administration & Security

### 20.1 Role-Based Access Control (RBAC)
- [ ] Predefined roles: Super Admin, School Admin, Principal, Vice Principal, HOD, Teacher, Class Teacher, Accountant, Librarian, Transport Manager, HR Manager, Warden, Reception/Counsellor, Parent, Student
- [ ] Custom role creation (add new role with specific permission set)
- [ ] Module-level permission: View / Create / Edit / Delete / Export
- [ ] Report-level permission (control who can see which reports)
- [ ] Field-level restriction (hide sensitive fields from certain roles)
- [ ] Role assignment to users
- [ ] Multiple role assignment to one user (e.g., Teacher + HOD)
- [ ] Role permission audit view (who has what)

### 20.2 User Account Management
- [ ] Create user accounts (for staff, parents auto-created on student enrollment)
- [ ] Username / email format configurable
- [ ] Temporary password auto-generated on account creation
- [ ] Bulk create parent accounts on student import
- [ ] Deactivate/reactivate user account
- [ ] Password reset by admin
- [ ] Force password change on first login
- [ ] Password strength policy (min length, complexity)
- [ ] Login activity log per user (last login, IP address)

### 20.3 Multi-Academic Year Management
- [ ] Create and manage multiple academic years
- [ ] Set active/current academic year
- [ ] View and access data for previous academic years (read-only)
- [ ] Year-end closing workflow (promote students, archive records)
- [ ] Prevent data entry on closed academic years

### 20.4 Audit Trail
- [ ] Log every data modification: module, action (create/update/delete), record ID, old value, new value, user, timestamp, IP address
- [ ] Audit log search and filter (by user, module, date range, action)
- [ ] Audit log view restricted to Super Admin only
- [ ] Audit log immutable (no delete/edit of audit records)

### 20.5 Backup & Restore (Shared Hosting Compatible)
- [ ] Scheduled daily database backup (mysqldump via cron)
- [ ] Scheduled weekly full file backup
- [ ] Backup file stored in a secure directory outside webroot
- [ ] Backup download by Super Admin
- [ ] Manual backup trigger from admin panel
- [ ] Restore database from backup file (admin-initiated)
- [ ] Backup retention policy (keep last 30 days, delete older)
- [ ] Backup success/failure email notification to admin

### 20.6 School Branding & Configuration
- [ ] School profile: name, address, phone, email, website, CBSE/Board affiliation number
- [ ] Logo upload (displayed on all PDF outputs, portal, reports)
- [ ] Colour theme selector for portal (primary/secondary colour)
- [ ] Letterhead configuration (logo position, school name font, address line)
- [ ] Principal name and digital signature upload (used on TCs, report cards)
- [ ] School stamp upload (for TC, certificates)
- [ ] Academic year display format configuration
- [ ] Date format preference (DD/MM/YYYY or MM/DD/YYYY)
- [ ] Currency symbol (INR ₹ default)

### 20.7 SMS & Communication Configuration
- [ ] SMS gateway API key and sender ID configuration
- [ ] WhatsApp API credentials configuration
- [ ] SMTP email server configuration
- [ ] FCM (Firebase) push notification key configuration
- [ ] Payment gateway API key configuration (Razorpay/PayU)
- [ ] GPS tracking device API configuration
- [ ] Configuration page accessible by Super Admin only

### 20.8 Security (Shared Hosting Compliant)
- [ ] HTTPS enforcement (force HTTPS redirect)
- [ ] Laravel built-in CSRF protection (all forms)
- [ ] XSS prevention (input sanitisation, Blade auto-escape)
- [ ] SQL injection prevention (Eloquent ORM, no raw queries)
- [ ] File upload validation (MIME type, extension whitelist, size limit)
- [ ] Uploaded files stored outside webroot (no direct URL access)
- [ ] Rate limiting on login endpoint (prevent brute force)
- [ ] Rate limiting on OTP generation endpoint
- [ ] Session security: secure + httponly cookie flags
- [ ] Session invalidation on logout
- [ ] Admin IP whitelist option (restrict admin panel to known IPs)

### 20.9 Multi-Language Support
- [ ] English language (default)
- [ ] Tamil language translation (UI strings)
- [ ] Hindi language translation (UI strings)
- [ ] Language selector on login page and user profile
- [ ] PDF outputs in selected language (report cards, TCs)
- [ ] SMS templates in Tamil/Hindi option

### 20.10 Data Privacy Compliance
- [ ] PDPB (India Personal Data Protection Bill) consent capture on admission
- [ ] Data processing consent checkbox with timestamp record
- [ ] Right to erasure: admin can anonymise/delete a student's personal data on request
- [ ] Data retention policy configuration (auto-archive after N years)
- [ ] Privacy policy page linked from portal login
- [ ] Aadhaar masking in all UI displays (only last 4 digits visible)

### 20.11 Application Performance (Shared Hosting)
- [ ] File-based caching for configuration and route data
- [ ] Database query caching for heavy reports
- [ ] Pagination on all list views (max 25/50/100 per page)
- [ ] Lazy loading of images and heavy components
- [ ] PDF generation queued via database queue driver (no Redis dependency)
- [ ] Bulk SMS queued via database queue driver processed by cron
- [ ] Optimised queries: no N+1 queries (use Eloquent eager loading)
- [ ] Database indexing on frequently searched columns
- [ ] Compressed static assets (CSS, JS minification)

### 20.12 API & Integration
- [ ] REST API with Laravel Sanctum authentication (for mobile app)
- [ ] API documentation (Postman collection or Swagger/OpenAPI)
- [ ] API rate limiting
- [ ] Webhook endpoint for payment gateway callbacks (Razorpay/PayU)
- [ ] Webhook endpoint for SMS delivery reports
- [ ] Webhook endpoint for GPS device data push
- [ ] API versioning (v1 prefix)

---

## DEPLOYMENT & HOSTING CHECKLIST (Shared Hosting)

### Environment Setup
- [ ] PHP 8.2 enabled with required extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, PDO_MySQL, Tokenizer, XML, Zip, GD/Imagick
- [ ] MySQL 8.x database created
- [ ] Composer dependencies installed (shared hosting Composer support)
- [ ] Laravel `.env` configured for shared hosting paths
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] `storage` and `bootstrap/cache` directories writable
- [ ] Document root pointed to `public/` directory
- [ ] `.htaccess` in place for Apache URL rewriting
- [ ] SSL certificate installed (Let's Encrypt or hosting provider SSL)

### Queue & Scheduler (No Daemon Process)
- [x] `QUEUE_CONNECTION=database` in `.env` (no Redis)
- [x] `jobs` table migrated
- [ ] Cron job configured: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
- [ ] `php artisan queue:work --stop-when-empty` called from scheduler (cron-based, not daemon)
- [ ] Test queued job processing (PDF generation, bulk SMS)

### File Storage
- [x] `FILESYSTEM_DISK=local` in `.env`
- [ ] Storage directory configured within shared hosting disk quota
- [ ] File upload size limits aligned with PHP `upload_max_filesize` and `post_max_size`
- [ ] Storage symlink created: `php artisan storage:link`

### Performance & Cache
- [x] `CACHE_DRIVER=file` in `.env`
- [x] `SESSION_DRIVER=file` in `.env`
- [ ] `php artisan config:cache` run post-deployment
- [ ] `php artisan route:cache` run post-deployment
- [ ] `php artisan view:cache` run post-deployment

---

*Document maintained by DC Innovision Pvt Ltd | Last Updated: June 2026*
