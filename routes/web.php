<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\AcademicController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ExaminationController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\HrController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\TransportController;
use App\Http\Controllers\Admin\HostelController;
use App\Http\Controllers\Admin\OnlineExamController;
use App\Http\Controllers\Admin\ClassesController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\AccountManagementController;
use App\Http\Controllers\Public\PublicFeePaymentController;

// ── Auth ──────────────────────────────────────────────────
Route::get('/',       [LoginController::class, 'landing'])->name('home');
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

// Public enquiry form (no auth)
Route::get('/enquiry',   [AdmissionController::class, 'enquiryForm'])->name('enquiry.form');
Route::post('/enquiry',  [AdmissionController::class, 'submitEnquiry'])->middleware('throttle:10,1')->name('enquiry.submit');

// Public application form (no auth)
Route::get('/apply/{token}',  [AdmissionController::class, 'publicApplicationForm'])->name('apply.form');
Route::post('/apply/{token}', [AdmissionController::class, 'submitPublicApplication'])->middleware('throttle:10,1')->name('apply.submit');
Route::get('/apply/success/{number}', [AdmissionController::class, 'applicationSuccess'])->name('apply.success');

// ── Public Student Document Portal via QR Code (no auth) ────
Route::get('/students/upload-docs/{token}',             [\App\Http\Controllers\Public\StudentDocumentUploadController::class, 'show'])->name('public.student.documents');
Route::post('/students/upload-docs/{token}',            [\App\Http\Controllers\Public\StudentDocumentUploadController::class, 'upload'])->middleware('throttle:30,1')->name('public.student.documents.upload');
Route::get('/students/upload-docs/{token}/doc/{docId}', [\App\Http\Controllers\Public\StudentDocumentUploadController::class, 'downloadDocument'])->name('public.student.documents.download');
Route::get('/students/upload-docs/{token}/download-qr', [\App\Http\Controllers\Public\StudentDocumentUploadController::class, 'downloadQr'])->name('public.student.documents.qr-download');
Route::get('/students/upload-docs/{token}/print-card',  [\App\Http\Controllers\Public\StudentDocumentUploadController::class, 'printCard'])->name('public.student.documents.print-card');

// ── Public Fee Payment Checkout via Link / QR (no auth) ────
Route::get('/pay/fee/{classId}',  [PublicFeePaymentController::class, 'show'])->name('public.fee.pay');
Route::post('/pay/fee/{classId}', [PublicFeePaymentController::class, 'processPayment'])->name('public.fee.process');

// ── Public Student Parent & Guardian Visitor Pass via QR Code (no auth) ────
Route::get('/visitor-card/view/{token}',   [\App\Http\Controllers\Public\VisitorCardController::class, 'viewPass'])->name('public.visitor-card.view');
Route::get('/visitor-card/verify/{token}', [\App\Http\Controllers\Public\VisitorCardController::class, 'verifyPass'])->name('public.visitor-card.verify');

// ── Public Gate Visitor Pass QR Verification (no auth) ────
Route::get('/gate/verify/{token}', [\App\Http\Controllers\Admin\GateController::class, 'verifyPass'])->name('gate.verify');

// ── Authenticated routes ──────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Custom Dashboard Widgets ────────────────────────────────
    Route::prefix('dashboard/widgets')->name('dashboard.widget.')->group(function () {
        Route::get('/preview',           [DashboardController::class, 'widgetPreview'])->name('preview');
        Route::post('/',                 [DashboardController::class, 'widgetStore'])->name('store');
        Route::post('/apply-settings',   [DashboardController::class, 'applySettings'])->name('apply-settings');
        Route::post('/reorder',          [DashboardController::class, 'widgetReorder'])->name('reorder');
        Route::delete('/{id}',           [DashboardController::class, 'widgetDestroy'])->name('destroy');
    });
    Route::post('/dashboard/widgets/apply-settings', [DashboardController::class, 'applySettings'])->name('dashboard.widgets.apply-settings');

    // ── Module 13 — Reports & Registers ───────────────────────
    Route::middleware('permission:view reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/general-register',      [DashboardController::class, 'generalRegister'])->name('general-register');
        Route::get('/tc-register',           [DashboardController::class, 'tcRegister'])->name('tc-register');
        Route::get('/fee-collection-register',[DashboardController::class, 'feeCollectionRegister'])->name('fee-collection-register');
        Route::get('/attendance-register',   [DashboardController::class, 'attendanceRegister'])->name('attendance-register');
    });

    // ── Module 1 — Admissions ──────────────────────────────
    Route::middleware('permission:view admissions')->prefix('admissions')->name('admissions.')->group(function () {
        Route::get('/',            [AdmissionController::class, 'index'])->name('index');
        Route::get('/create',      [AdmissionController::class, 'create'])->name('create');
        Route::get('/sibling-lookup', [AdmissionController::class, 'lookupSibling'])->name('sibling-lookup');
        Route::get('/print-form',  [AdmissionController::class, 'printForm'])->name('print-form');
        Route::post('/save-print-form', [AdmissionController::class, 'savePrintForm'])->name('save-print-form');
        Route::get('/fee-structure',[AdmissionController::class, 'feeStructure'])->name('fee-structure');
        Route::get('/fee-structure/{classId?}/print', [AdmissionController::class, 'printFeeStructure'])->name('fee-structure.print');
        Route::post('/',           [AdmissionController::class, 'store'])->name('store');
        // Pipeline & seats
        Route::get('/pipeline',    [AdmissionController::class, 'pipeline'])->name('pipeline');
        Route::post('/pipeline/{id}/advance', [AdmissionController::class, 'advanceStage'])->name('pipeline.advance');
        Route::get('/seats',       [AdmissionController::class, 'seats'])->name('seats');
        Route::post('/seats',      [AdmissionController::class, 'saveSeats'])->name('seats.save');
        Route::get('/bulk-import', [AdmissionController::class, 'bulkImport'])->name('bulk-import');
        Route::post('/bulk-import',[AdmissionController::class, 'processBulkImport'])->name('bulk-import.process');
        Route::get('/analytics',          [AdmissionController::class, 'analytics'])->name('analytics');
        Route::get('/analytics/pdf',      [AdmissionController::class, 'analyticsReportPdf'])->name('analytics.pdf');
        Route::get('/analytics/excel',    [AdmissionController::class, 'analyticsReportExcel'])->name('analytics.excel');
        Route::get('/export',             [AdmissionController::class, 'exportEnquiries'])->name('export');
        Route::post('/bulk-status',[AdmissionController::class, 'bulkStatusUpdate'])->name('bulk-status');
        Route::get('/application-form',  [AdmissionController::class, 'applicationForm'])->name('application-form');
        Route::post('/application-form', [AdmissionController::class, 'storeApplication'])->name('application-form.store');
        // Application Form Builder
        Route::get('/form-builder',                  [AdmissionController::class, 'formBuilder'])->name('form-builder');
        Route::post('/form-builder',                 [AdmissionController::class, 'storeFormConfig'])->name('form-builder.store');
        Route::post('/form-builder/{id}/toggle',     [AdmissionController::class, 'toggleFormConfig'])->name('form-builder.toggle');
        Route::delete('/form-builder/{id}',          [AdmissionController::class, 'deleteFormConfig'])->name('form-builder.delete');
        // Submitted applications
        Route::get('/applications',                  [AdmissionController::class, 'applications'])->name('applications');
        Route::post('/applications/{id}/status',     [AdmissionController::class, 'updateApplicationStatus'])->name('applications.status');
        Route::get('/applications/{id}/pdf',         [AdmissionController::class, 'applicationPdf'])->name('applications.pdf');
        Route::get('/{id}/confirmation-letter', [AdmissionController::class, 'confirmationLetter'])->name('confirmation-letter');
        // Reject with reason
        Route::post('/{id}/reject',            [AdmissionController::class, 'rejectAdmission'])->name('reject');
        // Waitlist management
        Route::get('/waitlist',                [AdmissionController::class, 'waitlist'])->name('waitlist');
        Route::post('/{id}/waitlist',          [AdmissionController::class, 'addToWaitlist'])->name('waitlist.add');
        Route::post('/{id}/waitlist/promote',  [AdmissionController::class, 'promoteFromWaitlist'])->name('waitlist.promote');
        // Entrance test + interview
        Route::post('/{id}/entrance-test',     [AdmissionController::class, 'saveEntranceTest'])->name('entrance-test.save');
        Route::get('/{id}/entrance-test-hallticket', [AdmissionController::class, 'entranceTestHallTicket'])->name('entrance-test.hallticket');
        Route::post('/{id}/interview',         [AdmissionController::class, 'saveInterview'])->name('interview.save');
        Route::post('/{id}/doc-checklist',     [AdmissionController::class, 'saveDocChecklist'])->name('doc-checklist.save');
        // Admissions 2-Tier Approvals & Submission Summary
        Route::get('/approvals',                [AdmissionController::class, 'approvals'])->name('approvals');
        Route::get('/{id}/submission-summary',  [AdmissionController::class, 'submissionSummary'])->name('submission-summary')->where('id', '[0-9]+');
        Route::post('/{id}/principal-approve',  [AdmissionController::class, 'principalApprove'])->name('principal-approve')->where('id', '[0-9]+');
        Route::post('/{id}/admin-confirm',      [AdmissionController::class, 'adminConfirm'])->name('admin-confirm')->where('id', '[0-9]+');
        Route::post('/{id}/reject-application', [AdmissionController::class, 'rejectApplication'])->name('reject-application')->where('id', '[0-9]+');

        // Single record routes — keep AFTER all named static routes to avoid catch-all collisions
        Route::get('/{id}',        [AdmissionController::class, 'show'])->name('show');
        Route::get('/{id}/edit',   [AdmissionController::class, 'edit'])->name('edit');
        Route::put('/{id}',        [AdmissionController::class, 'update'])->name('update');
        Route::delete('/{id}',     [AdmissionController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/status',[AdmissionController::class, 'updateStatus'])->name('status');
    });

    // ── Module 2 — Students ───────────────────────────────
    Route::middleware(['permission:view students', 'scope.user'])->prefix('students')->name('students.')->group(function () {
        Route::get('/',            [StudentController::class, 'index'])->name('index');
        Route::get('/create',      [StudentController::class, 'create'])->middleware('permission:create students')->name('create');
        Route::post('/',           [StudentController::class, 'store'])->middleware('permission:create students')->name('store');
        // Export & Import (Must be defined before /{id} parameter routes)
        Route::get('/export',              [StudentController::class, 'export'])->middleware('permission:export students')->name('export');
        Route::get('/import',              [StudentController::class, 'importForm'])->name('import');
        Route::post('/import',             [StudentController::class, 'processImport'])->name('import.process');
        Route::get('/import/template',     [StudentController::class, 'importTemplate'])->name('import.template');
        // Static sub-pages
        Route::get('/promotions',          [StudentController::class, 'promotions'])->name('promotions');
        Route::post('/promotions',         [StudentController::class, 'processPromotion'])->name('promotions.process');
        Route::post('/promotions/bulk',    [StudentController::class, 'processPromotion'])->name('promotions.bulk');
        Route::get('/rollover-report',     [StudentController::class, 'rolloverReport'])->name('rollover-report');
        Route::get('/id-cards',            [StudentController::class, 'idCards'])->name('id-cards');
        Route::get('/id-cards/pdf',        [StudentController::class, 'downloadIdCards'])->name('id-cards.pdf');
        Route::get('/id-card-template',    [StudentController::class, 'idCardTemplate'])->name('id-card-template');
        Route::post('/id-card-template',   [StudentController::class, 'saveIdCardTemplate'])->name('id-card-template.save');
        Route::get('/search-json',          [StudentController::class, 'searchJson'])->name('search-json');
        Route::get('/tc-register',         [StudentController::class, 'tcRegister'])->name('tc-register');
        Route::get('/directory',           [StudentController::class, 'directory'])->name('directory');
        Route::get('/tc-requests',              [StudentController::class, 'tcRequests'])->name('tc-requests');
        Route::post('/tc-requests',             [StudentController::class, 'storeTcRequest'])->name('tc-requests.store');
        Route::post('/tc-requests/{id}/approve',[StudentController::class, 'approveTcRequest'])->name('tc-requests.approve')->where('id', '[0-9]+');
        Route::post('/tc-requests/{id}/reject', [StudentController::class, 'rejectTcRequest'])->name('tc-requests.reject')->where('id', '[0-9]+');
        Route::get('/document-expiry',          [StudentController::class, 'documentExpiryReport'])->name('document-expiry');
        Route::patch('/documents/{docId}/verify', [StudentController::class, 'verifyDocument'])->name('documents.verify');
        Route::patch('/documents/{docId}/reject', [StudentController::class, 'rejectDocument'])->name('documents.reject');
        Route::delete('/documents/{docId}', [StudentController::class, 'deleteDocument'])->name('documents.delete');
        Route::delete('/concessions/{cId}',[StudentController::class, 'revokeConcession'])->name('concessions.revoke');

        // Parameterized routes on {id} (Defined after static routes)
        Route::get('/{id}',            [StudentController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::get('/{id}/fee-status', [StudentController::class, 'feeStatusJson'])->name('fee-status')->where('id', '[0-9]+');
        Route::get('/{id}/edit',       [StudentController::class, 'edit'])->middleware('permission:edit students')->name('edit')->where('id', '[0-9]+');
        Route::put('/{id}',        [StudentController::class, 'update'])->middleware('permission:edit students')->name('update')->where('id', '[0-9]+');
        Route::delete('/{id}',     [StudentController::class, 'destroy'])->middleware('permission:delete students')->name('destroy')->where('id', '[0-9]+');
        Route::get('/{id}/tc',     [StudentController::class, 'showTCForm'])->name('tc.form')->where('id', '[0-9]+');
        Route::post('/{id}/tc',    [StudentController::class, 'generateTC'])->name('tc')->where('id', '[0-9]+');
        Route::get('/{id}/id-card',[StudentController::class, 'singleIdCard'])->name('id-card.single')->where('id', '[0-9]+');
        Route::get('/{id}/visitor-card',[StudentController::class, 'visitorCard'])->name('visitor-card')->where('id', '[0-9]+');
        // Documents
        Route::get('/{id}/documents',     [StudentController::class, 'documents'])->name('documents')->where('id', '[0-9]+');
        Route::post('/{id}/documents',    [StudentController::class, 'uploadDocument'])->name('documents.upload')->where('id', '[0-9]+');
        Route::get('/{id}/documents/{docId}/download', [StudentController::class, 'downloadDocument'])->name('documents.download')->where('id', '[0-9]+')->where('docId', '[0-9]+');
        // Medical
        Route::get('/{id}/medical',      [StudentController::class, 'medical'])->name('medical')->where('id', '[0-9]+');
        Route::post('/{id}/medical',     [StudentController::class, 'saveMedical'])->name('medical.save')->where('id', '[0-9]+');
        Route::post('/{id}/vaccination', [StudentController::class, 'addVaccination'])->name('vaccination.add')->where('id', '[0-9]+');
        // Disciplinary
        Route::get('/{id}/disciplinary',   [StudentController::class, 'disciplinary'])->name('disciplinary')->where('id', '[0-9]+');
        Route::post('/{id}/disciplinary',  [StudentController::class, 'addDisciplinary'])->name('disciplinary.add')->where('id', '[0-9]+');
        // Concessions
        Route::get('/{id}/concessions',    [StudentController::class, 'concessions'])->name('concessions')->where('id', '[0-9]+');
        Route::post('/{id}/concessions',   [StudentController::class, 'grantConcession'])->name('concessions.grant')->where('id', '[0-9]+');
        // Shortage letter
        Route::get('/{id}/shortage-letter',[StudentController::class, 'shortageLetter'])->name('shortage-letter')->where('id', '[0-9]+');
        // Siblings
        Route::get('/{id}/siblings',        [StudentController::class, 'siblings'])->name('siblings')->where('id', '[0-9]+');
        Route::post('/{id}/siblings/link',  [StudentController::class, 'linkSibling'])->name('siblings.link')->where('id', '[0-9]+');
        Route::delete('/{id}/siblings/{siblingId}', [StudentController::class, 'unlinkSibling'])->name('siblings.unlink')->where('id', '[0-9]+');
        Route::post('/{id}/mark-left',      [StudentController::class, 'markAsLeft'])->name('mark-left')->where('id', '[0-9]+');
        Route::get('/{id}/disciplinary/{incidentId}/warning-letter', [StudentController::class, 'warningLetter'])->name('warning-letter')->where('id', '[0-9]+');
        // Mark student as left/transferred
        Route::post('/{id}/mark-as-left', [StudentController::class, 'markAsLeft'])->name('mark-as-left')->where('id', '[0-9]+');
        // Photo upload
        Route::post('/{id}/photo', [StudentController::class, 'uploadPhoto'])->name('photo.upload')->where('id', '[0-9]+');
    });

    // ── Module — Classes & Timetables ────────────────────
    Route::middleware('permission:view academics')->prefix('classes')->name('classes.')->group(function () {
        Route::get('/',                     [ClassesController::class, 'index'])->name('index');
        Route::put('/{id}/rename',          [ClassesController::class, 'rename'])->name('rename')->where('id', '[0-9]+');
        Route::post('/timetable/update-slot', [ClassesController::class, 'updateSlot'])->name('timetable.update-slot');
        Route::post('/timetable/toggle-holiday', [ClassesController::class, 'toggleHoliday'])->name('timetable.toggle-holiday');
        Route::post('/timetable/declare-holiday', [ClassesController::class, 'declareHoliday'])->name('timetable.declare-holiday');
        Route::delete('/timetable/delete-holiday/{id}', [ClassesController::class, 'deleteHoliday'])->name('timetable.delete-holiday');
        Route::get('/timetable/declared-holidays', [ClassesController::class, 'getHolidaysList'])->name('timetable.declared-holidays');
        Route::get('/{id}',                 [ClassesController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::get('/{id}/timetable-data',  [ClassesController::class, 'timetableData'])->name('timetable-data')->where('id', '[0-9]+');
        Route::get('/{id}/timetable/pdf',   [ClassesController::class, 'downloadPdf'])->name('timetable.pdf')->where('id', '[0-9]+');
        Route::get('/{id}/timetable/csv',   [ClassesController::class, 'exportCsv'])->name('timetable.csv')->where('id', '[0-9]+');
    });

    // ── Module 3 — Academics ──────────────────────────────
    Route::middleware('permission:view academics')->prefix('academics')->name('academics.')->group(function () {
        Route::get('/',       [AcademicController::class, 'index'])->name('index');
        Route::get('/timetable',  [AcademicController::class, 'timetable'])->name('timetable');
        Route::post('/timetable', [AcademicController::class, 'saveTimetable'])->name('timetable.save');
        Route::get('/subjects',   [AcademicController::class, 'subjects'])->name('subjects');
        Route::post('/subjects',  [AcademicController::class, 'storeSubject'])->name('subjects.store');
        Route::get('/syllabus',   [AcademicController::class, 'syllabus'])->name('syllabus');
        Route::post('/syllabus',  [AcademicController::class, 'saveSyllabus'])->name('syllabus.save');
        Route::post('/syllabus/batch', [AcademicController::class, 'batchStoreSyllabus'])->name('syllabus.batch');
        Route::get('/syllabus/print', [AcademicController::class, 'printSyllabus'])->name('syllabus.print');
        Route::post('/syllabus/{id}/document', [AcademicController::class, 'uploadSyllabusDocument'])->name('syllabus.document');
        Route::put('/syllabus/{id}',  [AcademicController::class, 'updateSyllabus'])->name('syllabus.update');
        Route::delete('/syllabus/{id}', [AcademicController::class, 'deleteSyllabus'])->name('syllabus.delete');
        Route::post('/syllabus/{id}/status', [AcademicController::class, 'updateSyllabusStatus'])->name('syllabus.status');
        Route::post('/syllabus/reorder', [AcademicController::class, 'reorderSyllabus'])->name('syllabus.reorder');
        // Lesson plans
        Route::get('/lesson-plans',              [AcademicController::class, 'lessonPlans'])->name('lesson-plans');
        Route::post('/lesson-plans',             [AcademicController::class, 'storeLessonPlan'])->name('lesson-plans.store');
        Route::post('/lesson-plans/{id}/review', [AcademicController::class, 'reviewLessonPlan'])->name('lesson-plans.review');
        Route::post('/lesson-plans/{id}/complete',[AcademicController::class,'markLessonComplete'])->name('lesson-plans.complete');
        Route::get('/lesson-plans/{id}/pdf',     [AcademicController::class, 'lessonPlanPdf'])->name('lesson-plans.pdf');
        // Syllabus coverage report
        Route::get('/syllabus-coverage',         [AcademicController::class, 'syllabusCoverage'])->name('syllabus-coverage');
        Route::get('/homework',   [AcademicController::class, 'homework'])->name('homework');
        Route::post('/homework',  [AcademicController::class, 'saveHomework'])->name('homework.save');
        // Homework calendar
        Route::get('/homework/calendar',               [AcademicController::class, 'homeworkCalendar'])->name('homework.calendar');
        // Homework submission tracking
        Route::get('/homework/{id}/submissions',       [AcademicController::class, 'homeworkSubmissions'])->name('homework.submissions');
        Route::post('/homework/{id}/submissions',      [AcademicController::class, 'saveSubmissionStatus'])->name('homework.submissions.save');
        Route::get('/homework/completion-report',      [AcademicController::class, 'homeworkCompletionReport'])->name('homework.completion-report');
        // Holidays
        Route::get('/holidays',        [AcademicController::class, 'holidays'])->name('holidays');
        Route::post('/holidays',       [AcademicController::class, 'addHoliday'])->name('holidays.add');
        Route::delete('/holidays/{id}',[AcademicController::class, 'deleteHoliday'])->name('holidays.delete');
        // Subject management (CRUD)
        Route::get('/subjects-manage',       [AcademicController::class, 'subjectsManage'])->name('subjects.manage');
        Route::post('/subjects',             [AcademicController::class, 'storeSubject'])->name('subjects.store');
        Route::put('/subjects/{id}',         [AcademicController::class, 'updateSubject'])->name('subjects.update');
        Route::post('/subjects/{id}/toggle', [AcademicController::class, 'toggleSubject'])->name('subjects.toggle');
        // Teacher allocation
        Route::get('/teacher-allocation',        [AcademicController::class, 'teacherAllocation'])->name('allocation');
        Route::post('/teacher-allocation',       [AcademicController::class, 'saveAllocation'])->name('allocation.save');
        Route::delete('/teacher-allocation/{id}',[AcademicController::class, 'deleteAllocation'])->name('allocation.delete');
        Route::get('/allocation-history',        [AcademicController::class, 'allocationHistory'])->name('allocation.history');
        // Academic year
        Route::get('/academic-year',             [AcademicController::class, 'academicYear'])->name('year');
        Route::post('/academic-year',            [AcademicController::class, 'saveAcademicYear'])->name('year.save');
        Route::post('/academic-year/{id}/set-current',[AcademicController::class,'setCurrentYear'])->name('year.set-current');
        // Academic year archive (read-only)
        Route::get('/year-archive',              [AcademicController::class, 'yearArchive'])->name('year-archive');
        Route::get('/year-archive/{yearId}/students',[AcademicController::class,'yearArchiveStudents'])->name('year-archive.students');
        // Notice board
        Route::get('/notices',            [AcademicController::class, 'notices'])->name('notices');
        Route::get('/notices/create',     [AcademicController::class, 'createNotice'])->name('notices.create');
        Route::post('/notices',           [AcademicController::class, 'storeNotice'])->name('notices.store');
        Route::get('/notices/{id}/edit',  [AcademicController::class, 'editNotice'])->name('notices.edit');
        Route::put('/notices/{id}',       [AcademicController::class, 'updateNotice'])->name('notices.update');
        Route::delete('/notices/{id}',    [AcademicController::class, 'deleteNotice'])->name('notices.delete');
        Route::post('/notices/{id}/read', [AcademicController::class, 'markNoticeRead'])->name('notices.read');
        Route::get('/notices/{id}/read-receipts', [AcademicController::class, 'noticeReadReceipts'])->name('notices.read-receipts');
        // Substitutions
        Route::get('/substitutions',       [AcademicController::class, 'substitutions'])->name('substitutions');
        Route::post('/substitutions',      [AcademicController::class, 'storeSubstitution'])->name('substitutions.store');
        Route::delete('/substitutions/{id}',[AcademicController::class, 'deleteSubstitution'])->name('substitutions.delete');
        // PDFs
        Route::get('/timetable/pdf',          [AcademicController::class, 'timetablePdf'])->name('timetable.pdf');
        Route::get('/timetable/teacher',      [AcademicController::class, 'teacherTimetable'])->name('timetable.teacher');
        Route::get('/timetable/workload',     [AcademicController::class, 'teacherWorkload'])->name('timetable.workload');
        Route::get('/timetable/conflicts',    [AcademicController::class, 'timetableConflicts'])->name('timetable.conflicts');
        Route::get('/calendar/pdf',       [AcademicController::class, 'calendarPdf'])->name('calendar.pdf');
        // Terms / Semesters
        Route::get('/terms',              [AcademicController::class, 'terms'])->name('terms');
        Route::post('/terms',             [AcademicController::class, 'storeTerm'])->name('terms.store');
        Route::put('/terms/{id}',         [AcademicController::class, 'updateTerm'])->name('terms.update');
        Route::delete('/terms/{id}',      [AcademicController::class, 'deleteTerm'])->name('terms.delete');
        // Copy holidays from previous year
        Route::post('/holidays/copy',     [AcademicController::class, 'copyHolidays'])->name('holidays.copy');
        // Working days config
        Route::get('/working-days',       [AcademicController::class, 'workingDays'])->name('working-days');
        Route::post('/working-days',      [AcademicController::class, 'saveWorkingDays'])->name('working-days.save');
        // Section management (co-class teacher, capacity, deactivation)
        Route::get('/sections-manage',                  [AcademicController::class, 'sectionsManage'])->name('sections.manage');
        Route::post('/sections',                        [AcademicController::class, 'createSection'])->name('sections.create');
        Route::put('/sections/{id}',                    [AcademicController::class, 'updateSection'])->name('sections.update');
        Route::post('/sections/{id}/deactivate-empty',  [AcademicController::class, 'deactivateSectionIfEmpty'])->name('sections.deactivate-empty');
        Route::put('/classes/{id}/rename',               [AcademicController::class, 'renameClass'])->name('classes.rename');
        Route::post('/sections/{id}/merge',              [AcademicController::class, 'mergeSection'])->name('sections.merge');
        Route::post('/sections/{id}/split',              [AcademicController::class, 'splitSection'])->name('sections.split');
    });

    // ── Module 4 — Attendance ─────────────────────────────
    Route::middleware(['permission:view attendance', 'scope.user'])->prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',          [AttendanceController::class, 'index'])->name('index');
        Route::get('/mark',      [AttendanceController::class, 'mark'])->name('mark');
        Route::post('/mark',     [AttendanceController::class, 'saveAttendance'])->name('save');
        Route::get('/report',    [AttendanceController::class, 'report'])->name('report');
        Route::get('/shortage',  [AttendanceController::class, 'shortage'])->name('shortage');
        Route::get('/shortage/{id}/letter', [AttendanceController::class, 'shortageLetter'])->name('shortage.letter');
        // Period-wise
        Route::get('/period-wise',  [AttendanceController::class, 'periodWise'])->name('period');
        Route::post('/period-wise', [AttendanceController::class, 'savePeriodAttendance'])->name('period.save');
        // Student leaves
        Route::get('/student-leave',        [AttendanceController::class, 'studentLeave'])->name('leave');
        Route::post('/student-leave/{id}/approve', [AttendanceController::class, 'approveLeave'])->name('leave.approve');
        Route::post('/student-leave/{id}/reject',  [AttendanceController::class, 'rejectLeave'])->name('leave.reject');
        // Staff attendance
        Route::get('/staff',   [AttendanceController::class, 'staffAttendance'])->name('staff');
        Route::post('/staff',  [AttendanceController::class, 'saveStaffAttendance'])->name('staff.save');
        Route::post('/staff/tap', [AttendanceController::class, 'tapStaffCard'])->name('staff.tap');
        Route::get('/staff/register', [AttendanceController::class, 'staffRegister'])->name('staff.register');
        Route::get('/staff/export/day',   [AttendanceController::class, 'exportDayWiseReport'])->name('staff.export.day');
        Route::get('/staff/export/month', [AttendanceController::class, 'exportMonthlyReport'])->name('staff.export.month');
        // Monthly register
        Route::get('/register',     [AttendanceController::class, 'register'])->name('register');
        Route::get('/register/pdf', [AttendanceController::class, 'registerPdf'])->name('register.pdf');
        // Reports
        Route::get('/chronic-absentees', [AttendanceController::class, 'chronicAbsentees'])->name('chronic');
        Route::get('/date-strength',     [AttendanceController::class, 'dateWiseStrength'])->name('date-strength');
        Route::get('/class-summary',     [AttendanceController::class, 'classSummary'])->name('class-summary');
        // Attendance condonation
        Route::get('/condonation',       [AttendanceController::class, 'condonation'])->name('condonation');
        Route::post('/condonation',      [AttendanceController::class, 'storeCondonation'])->name('condonation.store');
        // Subject-wise attendance report
        Route::get('/subject-report',    [AttendanceController::class, 'subjectAttendanceReport'])->name('subject-report');
        Route::get('/teacher-report',    [AttendanceController::class, 'teacherAttendanceReport'])->name('teacher-report');
        Route::get('/late-arrival',      [AttendanceController::class, 'lateArrivalReport'])->name('late-arrival');
        // Staff attendance dashboard
        Route::get('/staff/dashboard',   [AttendanceController::class, 'staffDashboard'])->name('staff.dashboard');
    });

    // ── Module 5 — Examinations ───────────────────────────
    Route::middleware(['permission:view examinations', 'scope.user'])->prefix('examinations')->name('examinations.')->group(function () {
        Route::get('/',              [ExaminationController::class, 'index'])->name('index');
        Route::get('/create',        [ExaminationController::class, 'create'])->name('create');
        Route::post('/',             [ExaminationController::class, 'store'])->name('store');
        Route::get('/marks-import-template', [ExaminationController::class, 'marksImportTemplate'])->name('marks-import-template');
        Route::get('/{id}/edit',     [ExaminationController::class, 'edit'])->name('edit');
        Route::put('/{id}',          [ExaminationController::class, 'update'])->name('update');
        Route::delete('/{id}',       [ExaminationController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/marks', [ExaminationController::class, 'marks'])->name('marks');
        Route::post('/{id}/marks',[ExaminationController::class, 'saveMarks'])->name('marks.save');
        Route::get('/{id}/results',[ExaminationController::class,'results'])->name('results');
        Route::get('/{id}/report-cards',[ExaminationController::class,'reportCards'])->name('report-cards');
        // Hall tickets & report cards (PDF)
        Route::get('/{id}/hall-tickets', [ExaminationController::class, 'hallTickets'])->name('hall-tickets');
        Route::get('/{id}/hall-tickets/bulk', [ExaminationController::class, 'hallTicketsBulk'])->name('hall-tickets.bulk');
        Route::get('/hall-ticket/{enrollmentId}/pdf', [ExaminationController::class, 'hallTicketPdf'])->name('hall-ticket.pdf');
        Route::get('/report-card/{enrollmentId}/pdf', [ExaminationController::class, 'reportCardPdf'])->name('report-card.pdf');
        // Grading schemes
        Route::get('/grading',             [ExaminationController::class, 'grading'])->name('grading');
        Route::post('/grading',            [ExaminationController::class, 'storeGrading'])->name('grading.store');
        Route::get('/grading/{id}/edit',   [ExaminationController::class, 'editGrading'])->name('grading.edit');
        Route::put('/grading/{id}',        [ExaminationController::class, 'updateGrading'])->name('grading.update');
        Route::post('/grading/{id}/default',[ExaminationController::class,'setDefaultGrading'])->name('grading.default');
        // Tabulation
        Route::get('/tabulation',         [ExaminationController::class, 'tabulation'])->name('tabulation');
        Route::get('/tabulation/export',  [ExaminationController::class, 'tabulationExport'])->name('tabulation.export');
        Route::get('/tabulation/pdf',     [ExaminationController::class, 'tabulationPdf'])->name('tabulation.pdf');
        // Bulk report cards / result reports
        Route::get('/{id}/report-cards/bulk-pdf', [ExaminationController::class, 'bulkReportCards'])->name('report-cards.bulk-pdf');
        Route::get('/class-result-summary', [ExaminationController::class, 'classResultSummary'])->name('class-result');
        Route::get('/cgpa-report',           [ExaminationController::class, 'cgpaReport'])->name('cgpa-report');
        Route::get('/failed-students',      [ExaminationController::class, 'failedStudents'])->name('failed');
        Route::get('/subject-performance',  [ExaminationController::class, 'subjectPerformance'])->name('subject-performance');
        Route::get('/student-result-history', [ExaminationController::class, 'studentResultHistory'])->name('student-result-history');
        // Marks Excel import
        Route::get('/marks-import',          [ExaminationController::class, 'marksImport'])->name('marks-import');
        Route::post('/marks-import',         [ExaminationController::class, 'processMarksImport'])->name('marks-import.process');
        // Grace marks config
        Route::post('/{id}/grace-marks',     [ExaminationController::class, 'saveGraceMarks'])->name('grace-marks');
        // Marks recheck workflow
        Route::get('/recheck-requests',      [ExaminationController::class, 'recheckRequests'])->name('recheck-requests');
        Route::post('/recheck-requests',     [ExaminationController::class, 'storeRecheckRequest'])->name('recheck-requests.store');
        Route::post('/recheck-requests/{id}',[ExaminationController::class, 'processRecheckRequest'])->name('recheck-requests.process');
        // Principal remarks per exam
        Route::post('/{id}/principal-remarks', [ExaminationController::class, 'savePrincipalRemarks'])->name('principal-remarks');
        // Question bank
        Route::get('/question-bank',        [ExaminationController::class, 'questionBank'])->name('qbank');
        Route::post('/question-bank',       [ExaminationController::class, 'storeQuestion'])->name('qbank.store');
        Route::get('/question-bank/{id}/edit',[ExaminationController::class,'editQuestion'])->name('qbank.edit');
        Route::put('/question-bank/{id}',  [ExaminationController::class, 'updateQuestion'])->name('qbank.update');
        Route::delete('/question-bank/{id}',[ExaminationController::class,'deleteQuestion'])->name('qbank.delete');
        // Marks progress indicator
        Route::get('/marks-progress',  [ExaminationController::class, 'marksProgress'])->name('marks-progress');
        // Promotion eligibility & merit scholarships
        Route::get('/promotion-eligibility',  [ExaminationController::class, 'promotionEligibility'])->name('promotion-eligibility');
        Route::post('/merit-scholarships',    [ExaminationController::class, 'applyMeritScholarships'])->name('merit-scholarships');
        // Marks lock / unlock
        Route::post('/{id}/lock-marks',   [ExaminationController::class, 'lockMarks'])->name('marks.lock');
        Route::post('/{id}/unlock-marks', [ExaminationController::class, 'unlockMarks'])->name('marks.unlock');
        // Cumulative marks across terms
        Route::get('/cumulative-marks',   [ExaminationController::class, 'cumulativeMarks'])->name('cumulative-marks');
        // Hall ticket blocks (fee defaulters + attendance shortage)
        Route::get('/hall-ticket-blocks', [ExaminationController::class, 'hallTicketBlocks'])->name('hall-ticket-blocks');
        // Co-scholastic grading
        Route::get('/{id}/coscholastic',  [ExaminationController::class, 'coscholasticEntry'])->name('coscholastic');
        Route::post('/{id}/coscholastic', [ExaminationController::class, 'saveCoscholasticGrades'])->name('coscholastic.save');

        // Question paper upload
        Route::get('/question-papers',               [ExaminationController::class, 'questionPapers'])->name('question-papers');
        Route::post('/question-papers',              [ExaminationController::class, 'uploadQuestionPaper'])->name('question-papers.upload');
        Route::get('/question-papers/{id}/download', [ExaminationController::class, 'downloadQuestionPaper'])->name('question-papers.download');
        Route::get('/question-papers/{id}/print',    [ExaminationController::class, 'printQuestionPaper'])->name('question-papers.print');
        Route::delete('/question-papers/{id}',       [ExaminationController::class, 'deleteQuestionPaper'])->name('question-papers.delete');
        // Question paper assembly from question bank
        Route::get('/assemble-paper',                [ExaminationController::class, 'assembleQuestionPaper'])->name('assemble-paper');
        Route::post('/assemble-paper/generate',      [ExaminationController::class, 'generateAssembledPaper'])->name('assemble-paper.generate');
        // Question paper templates
        Route::get('/paper-templates',               [ExaminationController::class, 'questionPaperTemplates'])->name('paper-templates');
        Route::post('/paper-templates',              [ExaminationController::class, 'storeQuestionPaperTemplate'])->name('paper-templates.store');
        Route::delete('/paper-templates/{id}',       [ExaminationController::class, 'deleteQuestionPaperTemplate'])->name('paper-templates.delete');
        // Comparative analysis
        Route::get('/comparative-analysis',  [ExaminationController::class, 'comparativeAnalysis'])->name('comparative-analysis');
        Route::get('/comparative-analysis/pdf', [ExaminationController::class, 'comparativeAnalysisPdf'])->name('comparative-analysis.pdf');
        Route::get('/class-result-summary/pdf',  [ExaminationController::class, 'classResultSummaryPdf'])->name('class-result.pdf');
        Route::get('/class-result-summary/excel',[ExaminationController::class, 'classResultSummaryExcel'])->name('class-result.excel');
        Route::get('/failed-students/pdf',        [ExaminationController::class, 'failedStudentsPdf'])->name('failed.pdf');
        Route::get('/failed-students/excel',      [ExaminationController::class, 'failedStudentsExcel'])->name('failed.excel');
        // NEP 2020 Competency Framework
        Route::get('/competencies',                [ExaminationController::class, 'competencies'])->name('competencies');
        Route::post('/competencies',               [ExaminationController::class, 'storeCompetency'])->name('competencies.store');
        Route::delete('/competencies/{id}',        [ExaminationController::class, 'deleteCompetency'])->name('competencies.delete');
        Route::get('/competency-assessment',       [ExaminationController::class, 'competencyAssessment'])->name('competency-assessment');
        Route::post('/competency-assessment',      [ExaminationController::class, 'saveCompetencyAssessment'])->name('competency-assessment.save');
        Route::get('/competency-report',           [ExaminationController::class, 'competencyReport'])->name('competency-report');
        Route::get('/coscholastic-nep',            [ExaminationController::class, 'coscholasticAssessment'])->name('coscholastic-nep');
        Route::post('/coscholastic-nep',           [ExaminationController::class, 'saveCoscholasticAssessment'])->name('coscholastic.save-nep');
        Route::get('/activity-records',            [ExaminationController::class, 'activityRecords'])->name('activity-records');
        Route::post('/activity-records',           [ExaminationController::class, 'storeActivityRecord'])->name('activity-records.store');
        // Email result notifications with report card PDF
        Route::get('/{id}/email-results',          [ExaminationController::class, 'emailResultNotifications'])->name('email-results');
        Route::post('/{id}/email-results',         [ExaminationController::class, 'sendResultEmails'])->name('email-results.send');
        // Exam schedule CRUD
        Route::post('/{id}/schedules',             [ExaminationController::class, 'storeSchedule'])->name('schedules.store');
        Route::delete('/schedules/{scheduleId}',   [ExaminationController::class, 'destroySchedule'])->name('schedules.destroy');
    });

    // ── Online Exams ──────────────────────────────────────
    Route::middleware('permission:view examinations')->prefix('online-exams')->name('online-exams.')->group(function () {
        // Admin
        Route::get('/',                          [OnlineExamController::class, 'index'])->name('index');
        Route::get('/create',                    [OnlineExamController::class, 'create'])->name('create');
        Route::post('/',                         [OnlineExamController::class, 'store'])->name('store');
        Route::get('/{id}/questions',            [OnlineExamController::class, 'manageQuestions'])->name('questions');
        Route::post('/{id}/questions',           [OnlineExamController::class, 'addQuestion'])->name('question.add');
        Route::delete('/{id}/questions/{qId}',   [OnlineExamController::class, 'removeQuestion'])->name('question.remove');
        Route::post('/{id}/publish',             [OnlineExamController::class, 'publish'])->name('publish');
        Route::get('/{id}/attempts',             [OnlineExamController::class, 'attempts'])->name('attempts');
        Route::get('/evaluate/{attemptId}',      [OnlineExamController::class, 'evaluateAttempt'])->name('evaluate');
        Route::post('/evaluate/{attemptId}',     [OnlineExamController::class, 'saveEvaluation'])->name('evaluate.save');
        Route::delete('/{id}',                   [OnlineExamController::class, 'destroy'])->name('destroy');
        // Student
        Route::get('/my',                        [OnlineExamController::class, 'studentExams'])->name('student');
        Route::get('/start/{id}',                [OnlineExamController::class, 'startExam'])->name('start');
        Route::post('/attempt/{attemptId}/answer',[OnlineExamController::class, 'submitAnswer'])->name('submit-answer');
        Route::post('/attempt/{attemptId}/submit',[OnlineExamController::class, 'submitExam'])->name('submit');
        Route::get('/result/{attemptId}',        [OnlineExamController::class, 'result'])->name('result');
    });

    // ── Module 6 — Fees ───────────────────────────────────
    Route::middleware('permission:view fees')->prefix('fees')->name('fees.')->group(function () {
        Route::get('/',             [FeeController::class, 'index'])->name('index');
        Route::get('/structure',    [FeeController::class, 'structure'])->name('structure');
        Route::post('/structure',   [FeeController::class, 'saveStructure'])->middleware('permission:manage fee structure')->name('structure.save');
        Route::post('/heads',       [FeeController::class, 'storeFeeHead'])->name('heads.store');
        Route::get('/collect',      [FeeController::class, 'collect'])->name('collect');
        Route::post('/collect',     [FeeController::class, 'savePayment'])->middleware('permission:collect fees')->name('collect.save');
        // Advance payment
        Route::get('/advance',      [FeeController::class, 'advancePayments'])->name('advance-payments');
        Route::post('/advance',     [FeeController::class, 'storeAdvancePayment'])->name('advance.store');
        Route::post('/advance/apply',[FeeController::class, 'applyAdvance'])->name('advance.apply');
        Route::get('/defaulters',                        [FeeController::class, 'defaulters'])->name('defaulters');
        Route::post('/defaulters/{id}/block-portal',     [FeeController::class, 'blockPortalAccess'])->name('defaulters.block');
        Route::post('/defaulters/{id}/unblock-portal',   [FeeController::class, 'unblockPortalAccess'])->name('defaulters.unblock');
        Route::get('/receipt/{id}', [FeeController::class, 'receipt'])->name('receipt');
        Route::get('/report',       [FeeController::class, 'report'])->name('report');
        Route::get('/report/export',[FeeController::class, 'reportExport'])->name('report.export');
        // Concessions
        Route::get('/concessions',       [FeeController::class, 'concessions'])->name('concessions');
        Route::post('/concessions',      [FeeController::class, 'saveConcession'])->name('concessions.save');
        Route::delete('/concessions/{id}',[FeeController::class,'deleteConcession'])->name('concessions.delete');
        Route::get('/scholarship-renewals',[FeeController::class,'scholarshipRenewalReminders'])->name('scholarship-renewals');
        Route::get('/fine-report',         [FeeController::class, 'fineReport'])->name('fine-report');
        // Cheque management
        Route::get('/cheque-pending',             [FeeController::class, 'chequePending'])->name('cheque-pending');
        Route::post('/cheque/{id}/clear',         [FeeController::class, 'markChequeCleared'])->name('cheque.clear');
        Route::post('/cheque/{id}/bounce',        [FeeController::class, 'markChequeBounced'])->name('cheque.bounce');
        // Bank reconciliation
        Route::get('/bank-reconciliation',        [FeeController::class, 'bankReconciliation'])->name('bank-reconciliation');
        // Late fee rules
        Route::get('/late-fee',         [FeeController::class, 'lateFeeRules'])->name('late-fee');
        Route::post('/late-fee',        [FeeController::class, 'storeLateFee'])->name('late-fee.store');
        Route::delete('/late-fee/{id}', [FeeController::class, 'deleteLateFee'])->name('late-fee.delete');
        // Installment plans
        Route::get('/installments',         [FeeController::class, 'installmentPlans'])->name('installments');
        Route::post('/installments',        [FeeController::class, 'storeInstallmentPlan'])->name('installments.store');
        Route::get('/installments/{id}',    [FeeController::class, 'showInstallmentPlan'])->name('installments.show');
        Route::get('/installments/{id}/edit',[FeeController::class,'editInstallmentPlan'])->name('installments.edit');
        // Day book
        Route::get('/daybook',     [FeeController::class, 'daybook'])->name('daybook');
        Route::get('/daybook/pdf', [FeeController::class, 'daybookPdf'])->name('daybook.pdf');
        // Student ledger
        Route::get('/ledger',     [FeeController::class, 'ledger'])->name('ledger');
        Route::get('/ledger/pdf', [FeeController::class, 'ledgerPdf'])->name('ledger.pdf');
        // Cancellation
        Route::get('/cancellation',      [FeeController::class, 'cancellation'])->name('cancellation');
        Route::post('/cancel/{id}',      [FeeController::class, 'cancelReceipt'])->middleware('permission:delete fees')->name('cancel');
        // Tally export
        Route::get('/tally',              [FeeController::class, 'tallyExport'])->name('tally');
        Route::get('/tally/download',     [FeeController::class, 'tallyDownload'])->name('tally.download');
        Route::post('/tally/ledger-map',  [FeeController::class, 'saveTallyLedgerMap'])->name('tally.ledger-map');
        // Defaulter aging / demand notice / duplicate receipt
        Route::get('/defaulter-aging',        [FeeController::class, 'defaulterAging'])->name('defaulter-aging');
        Route::get('/defaulters/{id}/demand-notice', [FeeController::class, 'demandNotice'])->name('demand-notice');
        Route::get('/receipt/{id}/duplicate', [FeeController::class, 'duplicateReceipt'])->name('receipt.duplicate');
        // Collection reports
        Route::get('/collection/monthly',  [FeeController::class, 'monthlyCollection'])->name('collection.monthly');
        Route::get('/collection/annual',   [FeeController::class, 'annualCollection'])->name('collection.annual');
        Route::get('/outstanding',         [FeeController::class, 'outstandingBalance'])->name('outstanding');
        // Bulk fee assignment
        Route::get('/bulk-assign',         [FeeController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('/bulk-assign',        [FeeController::class, 'processBulkAssign'])->name('bulk-assign.process');
        // Fee revision
        Route::get('/revision',            [FeeController::class, 'feeRevision'])->name('revision');
        Route::post('/revision',           [FeeController::class, 'saveFeeRevision'])->name('revision.save');
        // Fee carry-forwards (balance rollover)
        Route::get('/carry-forwards',             [FeeController::class, 'carryForwards'])->name('carry-forwards');
        Route::post('/carry-forwards',            [FeeController::class, 'storeCarryForward'])->name('carry-forwards.store');
        Route::post('/carry-forwards/{id}/recovery',[FeeController::class,'recordCarryForwardRecovery'])->name('carry-forwards.recovery');
        // Defaulter Excel export
        Route::get('/defaulters/export',   [FeeController::class, 'exportDefaulters'])->name('defaulters.export');
        // Class-wise and fee-head-wise collection
        Route::get('/collection/class-wise', [FeeController::class, 'classWiseCollection'])->name('collection.class-wise');
        Route::get('/collection/fee-head',   [FeeController::class, 'feeHeadCollection'])->name('collection.fee-head');
        // Cashier-wise collection report
        Route::get('/collection/cashier',    [FeeController::class, 'cashierReport'])->name('collection.cashier');
        // Concession report
        Route::get('/concession-report',     [FeeController::class, 'concessionReport'])->name('concession-report');
        // Category-wise fee variation
        Route::get('/category-variations',   [FeeController::class, 'categoryFeeVariations'])->name('category-variations');
        Route::post('/category-variations',  [FeeController::class, 'saveCategoryFeeVariation'])->name('category-variations.save');
        // Individual student custom fee
        Route::get('/student-custom-fee',    [FeeController::class, 'studentCustomFee'])->name('student-custom-fee');
        Route::post('/student-custom-fee',   [FeeController::class, 'saveStudentCustomFee'])->name('student-custom-fee.save');
        // Fee change history log
        Route::get('/change-history',         [FeeController::class, 'feeChangeHistory'])->name('change-history');
        // Fee reminder schedule config
        Route::get('/reminder-config',        [FeeController::class, 'reminderConfig'])->name('reminder-config');
        Route::post('/reminder-config',       [FeeController::class, 'saveReminderConfig'])->name('reminder-config.save');
        Route::post('/reminders/send',        [FeeController::class, 'sendReminders'])->name('reminders.send');
    });

    // ── Module 6.1 — Expenses (Bifurcated: Maintenance vs Academic) ──
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/',             [ExpenseController::class, 'index'])->name('index');
        Route::get('/academic',     [ExpenseController::class, 'academic'])->name('academic');
        Route::get('/maintenance',  [ExpenseController::class, 'maintenance'])->name('maintenance');
        Route::get('/create',       [ExpenseController::class, 'create'])->name('create');
        Route::post('/',            [ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}',    [ExpenseController::class, 'show'])->name('show');
        Route::post('/{expense}/verify',  [ExpenseController::class, 'verify'])->name('verify');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::post('/{expense}/reject',  [ExpenseController::class, 'reject'])->name('reject');
    });

    // ── Module 6.2 — Account Management (UPI, Cash Boxes, Financial Ledger) ──
    Route::middleware('permission:view fees')->prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/',              [AccountManagementController::class, 'index'])->name('index');
        Route::get('/daybook',       [AccountManagementController::class, 'daybook'])->name('daybook');
        Route::post('/transfer',     [AccountManagementController::class, 'storeTransfer'])->name('transfer');
        Route::get('/export',        [AccountManagementController::class, 'export'])->name('export');
    });

    // ── Module 7 — HR & Payroll ───────────────────────────
    Route::middleware('permission:view employees')->prefix('hr')->name('hr.')->group(function () {
        Route::get('/',               [HrController::class, 'index'])->name('index');

        // Staff Approval (Admin is View Only; Backend API is created now and permanent for future Principal Login)
        Route::get('/staff-approvals',                   [HrController::class, 'staffApprovals'])->name('staff-approvals');
        Route::post('/staff-approvals/{id}/approve',     [HrController::class, 'approveStaff'])->name('staff-approvals.approve');
        Route::post('/staff-approvals/{id}/reject',      [HrController::class, 'rejectStaff'])->name('staff-approvals.reject');

        // Staff Approval API (Permanent backend contract for Principal login)
        Route::get('/api/staff-approvals',               [HrController::class, 'apiStaffApprovals'])->name('api.staff-approvals.index');
        Route::get('/api/staff-approvals/{id}',          [HrController::class, 'apiStaffApprovalDetail'])->name('api.staff-approvals.show');
        Route::post('/api/staff-approvals/{id}/approve', [HrController::class, 'approveStaff'])->name('api.staff-approvals.approve');
        Route::post('/api/staff-approvals/{id}/reject',  [HrController::class, 'rejectStaff'])->name('api.staff-approvals.reject');

        // Leave Application: Apply On Behalf API (Permanent backend contract)
        Route::post('/api/leaves/apply-on-behalf',       [HrController::class, 'apiApplyLeaveOnBehalf'])->name('api.leaves.apply-on-behalf');

        // Leave Approval (Admin is View Only; Leave Approval API is strictly NOT created now)
        Route::get('/leave-approvals',                   [HrController::class, 'leaveApprovals'])->name('leave-approvals');

        Route::get('/attendance/mark',                [HrController::class, 'markAttendance'])->name('attendance.mark');
        Route::post('/attendance/mark',               [HrController::class, 'saveAttendanceMark'])->name('attendance.mark.save');

        Route::get('/attendance/view',                [HrController::class, 'viewAttendance'])->name('attendance.view');
        Route::get('/attendance/status/{status}',     [HrController::class, 'viewStatusAttendance'])->name('attendance.status');
        Route::get('/attendance/absent',              [HrController::class, 'viewAbsentAttendance'])->name('attendance.absent');
        Route::get('/attendance/on-duty',             [HrController::class, 'viewOnDutyAttendance'])->name('attendance.on-duty');
        Route::get('/attendance/paid-off',            [HrController::class, 'viewPaidOffAttendance'])->name('attendance.paid-off');
        Route::get('/attendance/permission',          [HrController::class, 'viewPermissionAttendance'])->name('attendance.permission');
        Route::post('/attendance/permission/update-in-time', [HrController::class, 'updatePermissionInTime'])->name('attendance.permission.update-in-time');
        Route::post('/attendance/permission/add-session', [HrController::class, 'addPermissionSession'])->name('attendance.permission.add-session');
        Route::get('/attendance/staff/{id}',          [HrController::class, 'viewStaffAttendanceDetail'])->name('attendance.staff-detail');

        Route::get('/reports',                        [HrController::class, 'reports'])->name('reports');
        Route::get('/reports/export',                 [HrController::class, 'exportReportsExcel'])->name('reports.export');

        Route::get('/events',                         [HrController::class, 'events'])->name('events');
        Route::post('/events',                        [HrController::class, 'storeEvent'])->name('events.store');
        Route::delete('/events/{id}',                 [HrController::class, 'deleteEvent'])->name('events.delete');
        Route::get('/employees',      [HrController::class, 'employees'])->name('employees');
        Route::get('/employees/create',[HrController::class, 'createEmployee'])->middleware('permission:create employees')->name('employees.create');
        Route::post('/employees',     [HrController::class, 'storeEmployee'])->middleware('permission:create employees')->name('employees.store');
        Route::get('/employees/{id}', [HrController::class, 'showEmployee'])->name('employees.show');
        Route::get('/employees/{id}/edit',[HrController::class, 'editEmployee'])->middleware('permission:edit employees')->name('employees.edit');
        Route::put('/employees/{id}', [HrController::class, 'updateEmployee'])->middleware('permission:edit employees')->name('employees.update');
        Route::get('/employees/{id}/id-card', [HrController::class, 'singleEmployeeIdCard'])->name('employees.id-card');
        Route::get('/id-card-studio',                 [HrController::class, 'employeeIdCardStudio'])->name('id-card-studio');
        Route::get('/id-cards/download',              [HrController::class, 'generateStaffIdCards'])->name('id-cards.download');
        Route::post('/employees/{id}/update-photo', [HrController::class, 'updateEmployeePhoto'])->name('employees.update-photo');
        // Qualifications & Experience
        Route::post('/employees/{id}/qualifications',          [HrController::class, 'storeQualification'])->name('employees.qualifications.store');
        Route::put('/employees/{id}/qualifications/{qualId}',  [HrController::class, 'updateQualification'])->name('employees.qualifications.update');
        Route::delete('/employees/{id}/qualifications/{qualId}',[HrController::class,'deleteQualification'])->name('employees.qualifications.delete');
        Route::post('/employees/{id}/experiences',             [HrController::class, 'storeExperience'])->name('employees.experiences.store');
        Route::delete('/employees/{id}/experiences/{expId}',   [HrController::class, 'deleteExperience'])->name('employees.experiences.delete');
        Route::get('/payroll',        [HrController::class, 'payroll'])->name('payroll');
        Route::post('/payroll/process',[HrController::class, 'processPayroll'])->name('payroll.process');
        Route::post('/payroll/approve',[HrController::class, 'approvePayroll'])->name('payroll.approve');
        Route::post('/payroll/{id}/unlock',[HrController::class,'unlockPayroll'])->name('payroll.unlock');
        Route::get('/payroll/{id}/payslip',[HrController::class,'downloadPayslip'])->name('payroll.payslip');
        Route::post('/payroll/{id}/email-payslip',[HrController::class,'emailPayslip'])->name('payroll.email-payslip');
        Route::get('/payroll/{id}/payslip-protected',[HrController::class,'downloadPayslipProtected'])->name('payroll.payslip-protected');
        Route::get('/payroll/bank-transfer',[HrController::class,'bankTransfer'])->name('payroll.bank-transfer');
        Route::get('/payroll/salary-register',[HrController::class,'salaryRegister'])->name('payroll.salary-register');
        // Statutory challan generation
        Route::get('/payroll/challan',  [HrController::class, 'challanGeneration'])->name('payroll.challan');
        Route::get('/leaves',         [HrController::class, 'leaves'])->name('leaves');
        Route::get('/leaves/apply',   [HrController::class, 'applyLeaveForm'])->name('leaves.apply');
        Route::post('/leaves/apply',  [HrController::class, 'storeLeave'])->name('leaves.store');
        // Note: Leave Approval API (leaves.approve, leaves.reject) is strictly NOT created in this phase per requirements.
        // Departments & Designations
        Route::get('/departments',        [HrController::class, 'departments'])->name('departments');
        Route::post('/departments',       [HrController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{id}',   [HrController::class, 'updateDepartment'])->name('departments.update');
        Route::post('/designations',       [HrController::class, 'storeDesignation'])->name('designations.store');
        Route::put('/designations/{id}',  [HrController::class, 'updateDesignation'])->name('designations.update');
        // Service documents
        // Employee documents
        Route::post('/employees/{id}/documents',                      [HrController::class, 'uploadEmployeeDocument'])->name('employees.documents.upload');
        Route::get('/employees/{id}/documents/{docId}/download',      [HrController::class, 'downloadEmployeeDocument'])->name('employees.documents.download');
        Route::get('/employees/{id}/documents/{docId}/preview',       [HrController::class, 'previewEmployeeDocument'])->name('employees.documents.preview');
        Route::delete('/employees/{id}/documents/{docId}',            [HrController::class, 'deleteEmployeeDocument'])->name('employees.documents.delete');
        Route::post('/employees/{id}/documents/{docId}/verify',       [HrController::class, 'verifyEmployeeDocument'])->name('employees.documents.verify');
        // Class teacher assignment
        Route::post('/employees/{id}/class-teacher-assignment',       [HrController::class, 'assignClassTeacher'])->middleware('permission:edit employees')->name('employees.class-teacher-assignment');
        // Certifications & training
        Route::post('/employees/{id}/certifications',                 [HrController::class, 'storeCertification'])->name('employees.certifications.store');
        Route::get('/employees/{id}/certifications/{certId}/download',[HrController::class, 'downloadCertification'])->name('employees.certifications.download');
        Route::get('/employees/{id}/certifications/{certId}/preview', [HrController::class, 'previewCertification'])->name('employees.certifications.preview');
        Route::delete('/employees/{id}/certifications/{certId}',      [HrController::class, 'deleteCertification'])->name('employees.certifications.delete');
        Route::get('/employees/{id}/appointment-letter',  [HrController::class, 'appointmentLetter'])->name('employees.appointment-letter');
        Route::get('/employees/{id}/experience-certificate',[HrController::class,'experienceCertificate'])->name('employees.experience-certificate');
        // Leave calendar
        Route::get('/leave-calendar', [HrController::class, 'leaveCalendar'])->name('leave-calendar');
        // Bank transfer view
        Route::get('/bank-transfer', [HrController::class, 'bankTransferView'])->name('bank-transfer');
        // Leave balance tracker & operations
        Route::get('/leave-balance',              [HrController::class, 'leaveBalance'])->name('leave-balance');
        Route::post('/leaves/{id}/cancel',        [HrController::class, 'cancelLeave'])->name('leaves.cancel');
        Route::post('/leaves/carry-forward',      [HrController::class, 'carryForwardLeaves'])->name('leaves.carry-forward');
        // Bulk payslip PDF
        Route::get('/payroll/bulk-payslip',       [HrController::class, 'bulkPayslipPdf'])->name('payroll.bulk-payslip');
        // Service letters
        Route::get('/employees/{id}/relieving-letter', [HrController::class, 'relievingLetter'])->name('employees.relieving-letter');
        Route::get('/employees/{id}/noc',              [HrController::class, 'nocLetter'])->name('employees.noc');
        Route::get('/employees/{id}/increment-letter', [HrController::class, 'incrementLetter'])->name('employees.increment-letter');
        // Portal login & role management for employees
        Route::post('/employees/{id}/portal-login', [HrController::class, 'createPortalLogin'])->name('employees.portal-login');
        Route::post('/employees/{id}/assign-role',  [HrController::class, 'assignEmployeeRole'])->name('employees.assign-role');
        // Salary hold
        Route::post('/employees/{id}/salary-hold',  [HrController::class, 'holdSalary'])->name('employees.salary-hold');
        Route::post('/employees/{id}/salary-release',[HrController::class, 'releaseSalary'])->name('employees.salary-release');
        // Department-wise salary summary, leave history
        Route::get('/payroll/department-summary', [HrController::class, 'departmentSalary'])->name('payroll.department-summary');
        Route::get('/employees/{id}/leave-history', [HrController::class, 'leaveHistory'])->name('employees.leave-history');
        // Leave calendar
        Route::get('/leave-calendar', [HrController::class, 'leaveCalendar'])->name('leave-calendar');
        // Leave encashment (EL)
        Route::get('/leave-encashment',       [HrController::class, 'leaveEncashment'])->name('leave-encashment');
        Route::post('/leave-encashment',      [HrController::class, 'processLeaveEncashment'])->name('leave-encashment.process');
        // Salary advances
        Route::get('/salary-advances',              [HrController::class, 'salaryAdvances'])->name('salary-advances');
        Route::post('/salary-advances',             [HrController::class, 'storeSalaryAdvance'])->name('salary-advances.store');
        Route::post('/salary-advances/{id}/approve',[HrController::class, 'approveSalaryAdvance'])->name('salary-advances.approve');
        Route::post('/salary-advances/{id}/reject', [HrController::class, 'rejectSalaryAdvance'])->name('salary-advances.reject');
        Route::post('/salary-advances/{id}/recovery',[HrController::class,'recordAdvanceRecovery'])->name('salary-advances.recovery');
        // Statutory computations (PF/ESI/PT)
        Route::get('/statutory-report', [HrController::class, 'statutoryReport'])->name('statutory-report');
        // Annual appraisal
        Route::get('/appraisal',         [HrController::class, 'appraisalForm'])->name('appraisal');
        Route::post('/appraisal',        [HrController::class, 'saveAppraisal'])->name('appraisal.save');
        // Self-appraisal by employee
        Route::get('/self-appraisal',    [HrController::class, 'selfAppraisalForm'])->name('self-appraisal');
        Route::post('/self-appraisal',   [HrController::class, 'saveSelfAppraisal'])->name('self-appraisal.save');
        // Increment processing
        Route::post('/appraisal/{id}/increment', [HrController::class, 'processIncrement'])->name('appraisal.increment');
        // Loan management
        Route::get('/loans',             [HrController::class, 'loans'])->name('loans');
        Route::post('/loans',            [HrController::class, 'storeLoan'])->name('loans.store');
        Route::post('/loans/{id}/close', [HrController::class, 'closeLoan'])->name('loans.close');
        Route::post('/loans/{id}/payment',[HrController::class, 'recordLoanPayment'])->name('loans.payment');
        // Attendance-leave reconciliation
        Route::get('/attendance-leave-reconciliation', [HrController::class, 'attendanceLeaveReconciliation'])->name('attendance-leave-reconciliation');
        // TDS computation & Form 16
        Route::get('/tds-computation',          [HrController::class, 'tdsComputation'])->name('tds-computation');
        Route::post('/employees/{id}/tds-declaration', [HrController::class, 'saveTdsDeclaration'])->name('employees.tds-declaration');
        Route::get('/employees/{id}/form-16',   [HrController::class, 'form16Pdf'])->name('employees.form-16');

        Route::get('/staff-id-cards',           [HrController::class, 'staffIdCards'])->name('staff-id-cards');
        Route::get('/staff-id-cards/pdf',       [HrController::class, 'downloadStaffIdCards'])->name('staff-id-cards.pdf');

        Route::get('/overtime',                 [HrController::class, 'overtime'])->name('overtime');
        Route::post('/overtime',                [HrController::class, 'storeOvertime'])->name('overtime.store');
        Route::get('/arrears-bonus',            [HrController::class, 'arrearsBonus'])->name('arrears-bonus');
        Route::post('/arrears-bonus',           [HrController::class, 'storeArrearsBonus'])->name('arrears-bonus.store');

        Route::get('/increment-history',        [HrController::class, 'incrementHistory'])->name('increment-history');
        Route::get('/increment-history/pdf',    [HrController::class, 'incrementHistoryPdf'])->name('increment-history.pdf');
        Route::get('/increment-history/excel',  [HrController::class, 'incrementHistoryExcel'])->name('increment-history.excel');
        Route::get('/leave-encashment-report',  [HrController::class, 'leaveEncashmentReport'])->name('leave-encashment-report');
        Route::get('/leave-encashment-report/pdf',  [HrController::class, 'leaveEncashmentReportPdf'])->name('leave-encashment-report.pdf');
        Route::get('/leave-encashment-report/excel',[HrController::class, 'leaveEncashmentReportExcel'])->name('leave-encashment-report.excel');
        Route::get('/payroll/salary-register/pdf',  [HrController::class, 'salaryRegisterPdf'])->name('payroll.salary-register.pdf');
        Route::get('/payroll/salary-register/excel',[HrController::class, 'salaryRegisterExcel'])->name('payroll.salary-register.excel');
        // Reporting hierarchy & org chart
        Route::get('/org-chart',                    [HrController::class, 'orgChart'])->name('org-chart');
        Route::post('/employees/{id}/set-manager',  [HrController::class, 'setManager'])->name('employees.set-manager');
    });

    // ── Module 8 — Library ────────────────────────────────
    Route::middleware('permission:view library')->prefix('library')->name('library.')->group(function () {
        Route::get('/',          [LibraryController::class, 'index'])->name('index');
        Route::get('/books',     [LibraryController::class, 'books'])->name('books');
        Route::get('/books/create',[LibraryController::class,'createBook'])->name('books.create');
        Route::post('/books',    [LibraryController::class, 'storeBook'])->name('books.store');
        Route::get('/isbn-lookup',[LibraryController::class,'isbnLookup'])->name('isbn-lookup');
        Route::get('/books/{id}/edit',[LibraryController::class,'editBook'])->name('books.edit');
        Route::put('/books/{id}',[LibraryController::class, 'updateBook'])->name('books.update');
        Route::get('/issue',     [LibraryController::class, 'issue'])->name('issue');
        Route::post('/issue',    [LibraryController::class, 'issueBook'])->name('issue.store');
        Route::post('/return',   [LibraryController::class, 'returnBook'])->name('return');
        Route::get('/overdue',   [LibraryController::class, 'overdue'])->name('overdue');
        // Reservations
        Route::get('/reservations',         [LibraryController::class, 'reservations'])->name('reservations');
        Route::post('/reservations',        [LibraryController::class, 'storeReservation'])->name('reservations.store');
        Route::post('/reservations/{id}/ready', [LibraryController::class,'markReservationReady'])->name('reservations.ready');
        Route::post('/reservations/{id}/cancel',[LibraryController::class,'cancelReservation'])->name('reservations.cancel');
        // Fine waiver
        Route::get('/fine-waiver',    [LibraryController::class, 'fineWaiver'])->name('fine.waiver');
        Route::post('/fine/{id}/waive',[LibraryController::class,'waiverApply'])->name('fine.waive');
        Route::post('/fine/{id}/collect-via-fee',[LibraryController::class,'collectFineViaFee'])->name('fine.collect-via-fee');
        // Digital resources
        Route::get('/digital',        [LibraryController::class, 'digitalResources'])->name('digital');
        Route::post('/digital',       [LibraryController::class, 'storeDigital'])->name('digital.store');
        Route::get('/digital/{id}',   [LibraryController::class, 'viewDigital'])->name('digital.view');
        Route::get('/digital/{id}/download',[LibraryController::class,'downloadDigital'])->name('digital.download');
        Route::delete('/digital/{id}',[LibraryController::class, 'deleteDigital'])->name('digital.delete');
        // Library fine rate settings
        Route::get('/settings',     [LibraryController::class, 'settings'])->name('settings');
        Route::post('/settings',    [LibraryController::class, 'saveSettings'])->name('settings.save');
        // Fine outstanding per member
        Route::get('/members/{id}/outstanding', [LibraryController::class, 'memberOutstanding'])->name('members.outstanding');
        // Stock register
        Route::get('/stock',        [LibraryController::class, 'stockRegister'])->name('stock');
        Route::get('/stock/export', [LibraryController::class, 'exportStock'])->name('stock.export');
        // Member cards
        Route::get('/members',         [LibraryController::class, 'members'])->name('members');
        Route::get('/members/{id}/card',[LibraryController::class,'memberCard'])->name('members.card');
        Route::get('/members/{id}/history',[LibraryController::class,'memberHistory'])->name('members.history');
        // Book renewal
        Route::post('/issue/{id}/renew', [LibraryController::class, 'renewBook'])->name('issue.renew');
        // Reports
        Route::get('/currently-issued',  [LibraryController::class, 'currentlyIssued'])->name('currently-issued');
        Route::get('/overdue-report',    [LibraryController::class, 'overdueReport'])->name('overdue-report');
        Route::get('/most-borrowed',     [LibraryController::class, 'mostBorrowed'])->name('most-borrowed');
        Route::get('/fine-defaulters',   [LibraryController::class, 'fineDefaulters'])->name('fine-defaulters');
        Route::post('/issue/{id}/mark-lost', [LibraryController::class, 'markLost'])->name('issue.mark-lost');

        Route::post('/books/{id}/cover',      [LibraryController::class, 'uploadCover'])->name('books.cover');
        Route::post('/books/{id}/deaccession',[LibraryController::class, 'deaccessionBook'])->name('books.deaccession');
        Route::get('/bulk-import',            [LibraryController::class, 'bulkImportForm'])->name('books.bulk-import');
        Route::post('/bulk-import',           [LibraryController::class, 'processBulkImport'])->name('books.bulk-import.process');
        Route::get('/issue/{id}/receipt',     [LibraryController::class, 'issueReceipt'])->name('issue.receipt');
        Route::get('/fine-report',            [LibraryController::class, 'fineReport'])->name('fine-report');
        Route::get('/stock-audit',            [LibraryController::class, 'stockAudit'])->name('stock-audit');
        Route::get('/acquisition-report',     [LibraryController::class, 'acquisitionReport'])->name('acquisition-report');
        Route::get('/deaccession-register',   [LibraryController::class, 'deaccessionRegister'])->name('deaccession-register');
        // Book QR / Barcode labels
        Route::get('/books/{id}/qr-label',    [LibraryController::class, 'bookQrLabel'])->name('books.qr-label');
        Route::get('/books/qr-bulk',          [LibraryController::class, 'bulkQrLabels'])->name('books.qr-bulk');
        // Member status (suspend / unsuspend)
        Route::post('/members/{id}/suspend',   [LibraryController::class, 'suspendMember'])->name('members.suspend');
        Route::post('/members/{id}/unsuspend', [LibraryController::class, 'unsuspendMember'])->name('members.unsuspend');
        // Overdue email reminders
        Route::post('/send-overdue-reminders', [LibraryController::class, 'sendOverdueReminders'])->name('send-overdue-reminders');
    });

    // ── Module 9 — Transport ─────────────────────────────
    Route::middleware('permission:view transport')->prefix('transport')->name('transport.')->group(function () {
        Route::get('/',         [TransportController::class, 'index'])->name('index');
        // Routes CRUD
        Route::get('/routes',                   [TransportController::class, 'routes'])->name('routes');
        Route::get('/routes/create',            [TransportController::class, 'createRoute'])->name('routes.create');
        Route::post('/routes',                  [TransportController::class, 'storeRoute'])->name('routes.store');
        Route::get('/routes/{id}/edit',         [TransportController::class, 'editRoute'])->name('routes.edit');
        Route::put('/routes/{id}',              [TransportController::class, 'updateRoute'])->name('routes.update');
        Route::patch('/routes/{id}/toggle',     [TransportController::class, 'toggleRoute'])->name('routes.toggle');
        // Vehicles CRUD
        Route::get('/vehicles',       [TransportController::class, 'vehicles'])->name('vehicles');
        Route::get('/vehicles/create',[TransportController::class,'createVehicle'])->name('vehicles.create');
        Route::post('/vehicles',      [TransportController::class,'storeVehicle'])->name('vehicles.store');
        Route::get('/vehicles/{id}/edit',[TransportController::class,'editVehicle'])->name('vehicle.edit');
        Route::put('/vehicles/{id}',  [TransportController::class,'updateVehicle'])->name('vehicle.update');
        // Drivers
        Route::get('/drivers',                           [TransportController::class, 'drivers'])->name('drivers');
        Route::get('/drivers/create',                    [TransportController::class, 'createDriver'])->name('drivers.create');
        Route::post('/drivers',                          [TransportController::class, 'storeDriver'])->name('drivers.store');
        Route::post('/drivers/{id}/police-verification', [TransportController::class, 'updatePoliceVerification'])->name('drivers.police-verification');
        // Incident log
        Route::get('/incidents',              [TransportController::class, 'incidents'])->name('incidents');
        Route::post('/incidents',             [TransportController::class, 'storeIncident'])->name('incidents.store');
        Route::post('/incidents/{id}/status', [TransportController::class, 'updateIncidentStatus'])->name('incidents.update-status');
        // Emergency contacts per route
        Route::get('/emergency-contacts',            [TransportController::class, 'emergencyContacts'])->name('emergency-contacts');
        Route::post('/emergency-contacts',           [TransportController::class, 'storeEmergencyContact'])->name('emergency-contacts.store');
        Route::delete('/emergency-contacts/{id}',    [TransportController::class, 'deleteEmergencyContact'])->name('emergency-contacts.delete');
        // Stops CRUD
        Route::get('/stops',         [TransportController::class, 'stops'])->name('stops');
        Route::post('/stops',        [TransportController::class, 'storeStop'])->name('stops.store');
        Route::get('/stops/{id}/edit',[TransportController::class,'editStop'])->name('stops.edit');
        Route::put('/stops/{id}',    [TransportController::class, 'updateStop'])->name('stops.update');
        Route::delete('/stops/{id}', [TransportController::class, 'deleteStop'])->name('stops.delete');
        Route::get('/tracking', [TransportController::class, 'tracking'])->name('tracking');
        Route::get('/tracking/telemetry', [TransportController::class, 'liveTelemetry'])->name('tracking.telemetry');
        // Student allotment
        Route::get('/allotment',         [TransportController::class, 'allotment'])->name('allotment');
        Route::post('/allotment',        [TransportController::class, 'storeAllotment'])->name('allotment.store');
        Route::delete('/allotment/{id}', [TransportController::class, 'deleteAllotment'])->name('allotment.delete');
        Route::get('/allotment/export',  [TransportController::class, 'exportAllotment'])->name('allotment.export');
        // Document expiry
        Route::get('/documents', [TransportController::class, 'vehicleDocuments'])->name('documents');
        // Maintenance
        Route::get('/maintenance',   [TransportController::class, 'maintenance'])->name('maintenance');
        Route::post('/maintenance',  [TransportController::class, 'storeMaintenance'])->name('maintenance.store');
        // Fuel log
        Route::get('/fuel',   [TransportController::class, 'fuel'])->name('fuel');
        Route::post('/fuel',  [TransportController::class, 'storeFuel'])->name('fuel.store');
        // Route roster
        Route::get('/roster', [TransportController::class, 'roster'])->name('roster');
        // Attendant master
        Route::get('/attendants',        [TransportController::class, 'attendants'])->name('attendants');
        Route::post('/attendants',       [TransportController::class, 'storeAttendant'])->name('attendants.store');
        Route::put('/attendants/{id}',   [TransportController::class, 'updateAttendant'])->name('attendants.update');
        Route::delete('/attendants/{id}',[TransportController::class, 'deleteAttendant'])->name('attendants.delete');
        // Vehicle utilisation report
        Route::get('/vehicle-utilisation',[TransportController::class, 'vehicleUtilisation'])->name('vehicle-utilisation');
        // Fuel expense monthly summary
        Route::get('/fuel-summary',       [TransportController::class, 'fuelSummary'])->name('fuel-summary');
        Route::get('/fuel-summary/pdf',   [TransportController::class, 'fuelSummaryPdf'])->name('fuel-summary.pdf');
        Route::get('/fuel-summary/excel', [TransportController::class, 'fuelSummaryExcel'])->name('fuel-summary.excel');
        // Roster exports
        Route::get('/roster/pdf',         [TransportController::class, 'rosterPdf'])->name('roster.pdf');
        Route::get('/roster/excel',       [TransportController::class, 'rosterExcel'])->name('roster.excel');
        // Maintenance cost per vehicle report
        Route::get('/maintenance-cost',        [TransportController::class, 'maintenanceCostReport'])->name('maintenance-cost');
        Route::get('/maintenance-cost/pdf',    [TransportController::class, 'maintenanceCostPdf'])->name('maintenance-cost.pdf');
        Route::get('/maintenance-cost/excel',  [TransportController::class, 'maintenanceCostExcel'])->name('maintenance-cost.excel');
        // Bus attendance register
        Route::get('/bus-attendance',                [TransportController::class, 'busAttendance'])->name('bus-attendance');
        Route::post('/bus-attendance',               [TransportController::class, 'saveBusAttendance'])->name('bus-attendance.save');
        Route::get('/bus-attendance/report',         [TransportController::class, 'busAttendanceReport'])->name('bus-attendance.report');
        Route::get('/bus-attendance/report/excel',   [TransportController::class, 'busAttendanceReportExcel'])->name('bus-attendance.report.excel');
    });

    // ── Module 10 — Hostel ────────────────────────────────
    Route::middleware('permission:view hostel')->prefix('hostel')->name('hostel.')->group(function () {
        Route::get('/',                       [HostelController::class, 'index'])->name('index');
        Route::post('/buildings',             [HostelController::class, 'storeBuilding'])->name('building.store');
        Route::put('/buildings/{id}',         [HostelController::class, 'updateBuilding'])->name('building.update');
        Route::get('/rooms',                  [HostelController::class, 'rooms'])->name('rooms');
        Route::get('/allotment', [HostelController::class, 'allotment'])->name('allotment');
        Route::post('/allotment',[HostelController::class, 'saveAllotment'])->name('allotment.save');
        Route::get('/mess',      [HostelController::class, 'mess'])->name('mess');
        Route::get('/outpass',   [HostelController::class, 'outpass'])->name('outpass');
        // Fee config
        Route::get('/fee-config',  [HostelController::class, 'feeConfig'])->name('fee.config');
        Route::post('/fee-config', [HostelController::class, 'storeFeeConfig'])->name('fee.store');
        // Visitors
        Route::get('/visitors',                  [HostelController::class, 'visitors'])->name('visitors');
        Route::post('/visitors',                 [HostelController::class, 'storeVisitor'])->name('visitors.store');
        Route::post('/visitors/{id}/checkout',   [HostelController::class, 'checkoutVisitor'])->name('visitors.checkout');
        Route::get('/visitors/{id}/pass',        [HostelController::class, 'visitorPass'])->name('visitors.pass');
        // Visiting hours config
        Route::get('/visiting-hours',            [HostelController::class, 'visitingHoursConfig'])->name('visiting-hours');
        Route::post('/visiting-hours',           [HostelController::class, 'storeVisitingHours'])->name('visiting-hours.store');
        Route::delete('/visiting-hours/{id}',    [HostelController::class, 'deleteVisitingHours'])->name('visiting-hours.delete');
        // Complaints
        Route::get('/complaints',                    [HostelController::class, 'complaints'])->name('complaints');
        Route::post('/complaints',                   [HostelController::class, 'storeComplaint'])->name('complaints.store');
        Route::patch('/complaints/{id}',             [HostelController::class, 'updateComplaint'])->name('complaints.update');
        Route::get('/complaints/room/{roomId}',      [HostelController::class, 'complaintsByRoom'])->name('complaints.by-room');
        // Late return / overdue outpasses
        Route::get('/overdue-outpasses',             [HostelController::class, 'overdueOutpasses'])->name('overdue-outpasses');
        // Outpass workflow
        Route::get('/outpass-workflow',              [HostelController::class, 'outpassWorkflow'])->name('outpass.workflow');
        Route::post('/outpass-workflow',             [HostelController::class, 'storeOutpass'])->name('outpass.store');
        Route::post('/outpass-workflow/{id}/approve',[HostelController::class,'approveOutpass'])->name('outpass.approve');
        Route::post('/outpass-workflow/{id}/reject', [HostelController::class,'rejectOutpass'])->name('outpass.reject');
        Route::post('/outpass-workflow/{id}/return', [HostelController::class,'returnOutpass'])->name('outpass.return');
        // Occupancy / room reports
        Route::get('/occupancy',       [HostelController::class, 'occupancy'])->name('occupancy');
        Route::get('/room-students',   [HostelController::class, 'roomStudents'])->name('room-students');
        Route::get('/fee-outstanding', [HostelController::class, 'feeOutstanding'])->name('fee-outstanding');
        // Warden management
        Route::get('/wardens',           [HostelController::class, 'wardens'])->name('wardens');
        Route::post('/wardens',          [HostelController::class, 'assignWarden'])->name('wardens.assign');
        Route::delete('/wardens/{id}',   [HostelController::class, 'removeWarden'])->name('wardens.remove');
        Route::get('/warden-contacts',   [HostelController::class, 'wardenContacts'])->name('warden-contacts');
        // Mess improvements
        Route::post('/mess/menu',     [HostelController::class, 'saveMealMenu'])->name('mess.menu');
        // Maintenance complaint status report
        Route::get('/complaints-report', [HostelController::class, 'complaintsReport'])->name('complaints-report');
        // Floor master
        Route::get('/floors',            [HostelController::class, 'floors'])->name('floors');
        Route::post('/floors',           [HostelController::class, 'storeFloor'])->name('floors.store');
        Route::put('/floors/{id}',       [HostelController::class, 'updateFloor'])->name('floors.update');
        Route::delete('/floors/{id}',    [HostelController::class, 'deleteFloor'])->name('floors.delete');
        // Room transfer
        Route::get('/transfer',          [HostelController::class, 'roomTransfer'])->name('transfer');
        Route::post('/transfer',         [HostelController::class, 'processRoomTransfer'])->name('transfer.process');
        // Warden duty roster
        Route::get('/warden-roster',     [HostelController::class, 'wardenRoster'])->name('warden-roster');
        Route::post('/warden-roster',    [HostelController::class, 'saveWardenRoster'])->name('warden-roster.save');
        // Student vacate
        Route::get('/vacate',            [HostelController::class, 'vacateStudent'])->name('vacate');
        Route::post('/vacate/{allotmentId}', [HostelController::class, 'processVacate'])->name('vacate.process');
        // Student hostel ID card
        Route::get('/id-card/{allotmentId}',     [HostelController::class, 'studentIdCard'])->name('id-card');
        // Bed master
        Route::get('/rooms/{roomId}/beds',       [HostelController::class, 'beds'])->name('beds');
        Route::post('/rooms/{roomId}/beds',      [HostelController::class, 'storeBed'])->name('beds.store');
        Route::patch('/beds/{bedId}',            [HostelController::class, 'updateBed'])->name('beds.update');
        Route::delete('/beds/{bedId}',           [HostelController::class, 'deleteBed'])->name('beds.delete');
        Route::post('/rooms/{roomId}/beds/bulk', [HostelController::class, 'bulkCreateBeds'])->name('beds.bulk');
        // Gate pass QR
        Route::get('/outpass/{id}/gate-pass-qr', [HostelController::class, 'gatePassQr'])->name('outpass.gate-pass-qr');
        // Reports export
        Route::get('/occupancy-report/pdf',       [HostelController::class, 'occupancyReportPdf'])->name('occupancy.pdf');
        Route::get('/occupancy-report/excel',     [HostelController::class, 'occupancyReportExcel'])->name('occupancy.excel');
        Route::get('/gate-pass-register/pdf',     [HostelController::class, 'gatePassRegisterPdf'])->name('gate-pass-register.pdf');
        Route::get('/gate-pass-register/excel',   [HostelController::class, 'gatePassRegisterExcel'])->name('gate-pass-register.excel');
        Route::get('/fee-outstanding/excel',      [HostelController::class, 'feeOutstandingExcel'])->name('fee-outstanding.excel');
        // Night duty
        Route::get('/night-duty',                 [HostelController::class, 'nightDuty'])->name('night-duty');
        Route::post('/night-duty',                [HostelController::class, 'storeNightDuty'])->name('night-duty.store');
        Route::delete('/night-duty/{id}',         [HostelController::class, 'deleteNightDuty'])->name('night-duty.delete');
        // Mess attendance
        Route::get('/mess-attendance',            [HostelController::class, 'messAttendance'])->name('mess-attendance');
        Route::post('/mess-attendance',           [HostelController::class, 'saveMessAttendance'])->name('mess-attendance.save');
        Route::get('/mess-attendance-summary',    [HostelController::class, 'messAttendanceSummary'])->name('mess-attendance-summary');
        // Mess rebate
        Route::get('/mess-rebates',              [HostelController::class, 'messRebates'])->name('mess-rebates');
        Route::post('/mess-rebates',             [HostelController::class, 'storeMessRebate'])->name('mess-rebates.store');
        Route::post('/mess-rebates/{id}/approve',[HostelController::class, 'approveMessRebate'])->name('mess-rebates.approve');
        Route::delete('/mess-rebates/{id}',      [HostelController::class, 'deleteMessRebate'])->name('mess-rebates.delete');
        // Hostel disciplinary & warnings
        Route::get('/disciplinary',              [HostelController::class, 'hostelDisciplinary'])->name('disciplinary');
        Route::post('/disciplinary',             [HostelController::class, 'storeHostelDisciplinary'])->name('disciplinary.store');
        Route::delete('/disciplinary/{id}',      [HostelController::class, 'deleteHostelDisciplinary'])->name('disciplinary.delete');
        // Mess expense tracking
        Route::get('/mess-expenses',             [HostelController::class, 'messExpenses'])->name('mess-expenses');
        Route::post('/mess-expenses',            [HostelController::class, 'storeMessExpense'])->name('mess-expenses.store');
        Route::delete('/mess-expenses/{id}',     [HostelController::class, 'deleteMessExpense'])->name('mess-expenses.delete');
        // Mess feedback
        Route::get('/mess-feedback',             [HostelController::class, 'messFeedback'])->name('mess-feedback');
        Route::post('/mess-feedback',            [HostelController::class, 'storeMessFeedback'])->name('mess-feedback.store');
        // Room CRUD
        Route::post('/rooms',          [HostelController::class, 'storeRoom'])->name('rooms.store');
        Route::put('/rooms/{id}',      [HostelController::class, 'updateRoom'])->name('rooms.update');
        Route::delete('/rooms/{id}',   [HostelController::class, 'destroyRoom'])->name('rooms.destroy');
    });

    // Visitors stub (no module built yet)
    Route::get('/visitors',  fn() => view('coming-soon', ['module' => 'Visitors']))->name('visitors.index');

    // ── Module 12 — Communication ──────────────────────────────
    Route::middleware('permission:send email')->prefix('communication')->name('communication.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Admin\CommunicationController::class, 'index'])->name('index');
        Route::get('/bulk-email',         [\App\Http\Controllers\Admin\CommunicationController::class, 'bulkEmail'])->name('bulk-email');
        Route::post('/bulk-email',        [\App\Http\Controllers\Admin\CommunicationController::class, 'sendBulkEmail'])->name('bulk-email.send');
        Route::get('/logs',               [\App\Http\Controllers\Admin\CommunicationController::class, 'logs'])->name('logs');
        Route::get('/staff-notices',      [\App\Http\Controllers\Admin\CommunicationController::class, 'staffNotices'])->name('staff-notices');
        Route::post('/staff-notices',     [\App\Http\Controllers\Admin\CommunicationController::class, 'storeStaffNotice'])->name('staff-notices.store');
        Route::delete('/staff-notices/{id}', [\App\Http\Controllers\Admin\CommunicationController::class, 'deleteStaffNotice'])->name('staff-notices.delete');
        Route::patch('/staff-notices/{id}/read', [\App\Http\Controllers\Admin\CommunicationController::class, 'markNoticeRead'])->name('staff-notices.read');
        Route::get('/staff-notices/{id}/receipts', [\App\Http\Controllers\Admin\CommunicationController::class, 'noticeReadReceipts'])->name('staff-notices.receipts');
        Route::get('/portal-accounts',    [\App\Http\Controllers\Admin\CommunicationController::class, 'portalAccounts'])->name('portal-accounts');
        Route::post('/portal-accounts',   [\App\Http\Controllers\Admin\CommunicationController::class, 'createPortalAccount'])->name('portal-accounts.create');
        Route::post('/portal-accounts/{id}/reset-password', [\App\Http\Controllers\Admin\CommunicationController::class, 'resetPortalPassword'])->name('portal-accounts.reset-password');
    });

    // ── Module 17 — LMS ───────────────────────────────────────
    Route::middleware('permission:view lms')->prefix('lms')->name('lms.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Admin\LmsController::class, 'index'])->name('index');
        // Courses
        Route::get('/courses/create',     [\App\Http\Controllers\Admin\LmsController::class, 'createCourse'])->name('courses.create');
        Route::post('/courses',           [\App\Http\Controllers\Admin\LmsController::class, 'storeCourse'])->name('courses.store');
        Route::get('/courses/{id}',       [\App\Http\Controllers\Admin\LmsController::class, 'showCourse'])->name('courses.show');
        Route::put('/courses/{id}',       [\App\Http\Controllers\Admin\LmsController::class, 'updateCourse'])->name('courses.update');
        Route::delete('/courses/{id}',    [\App\Http\Controllers\Admin\LmsController::class, 'destroyCourse'])->name('courses.destroy');
        // Units
        Route::post('/courses/{id}/units',      [\App\Http\Controllers\Admin\LmsController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{id}',               [\App\Http\Controllers\Admin\LmsController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units/{id}',            [\App\Http\Controllers\Admin\LmsController::class, 'deleteUnit'])->name('units.delete');
        Route::post('/courses/{course}/units/reorder', [\App\Http\Controllers\Admin\LmsController::class, 'reorderUnits'])->name('units.reorder');
        Route::post('/units/{unit}/lessons/reorder',   [\App\Http\Controllers\Admin\LmsController::class, 'reorderLessons'])->name('lessons.reorder');
        // Lessons
        Route::post('/units/{id}/lessons',      [\App\Http\Controllers\Admin\LmsController::class, 'storeLesson'])->name('lessons.store');
        Route::get('/lessons/{id}',             [\App\Http\Controllers\Admin\LmsController::class, 'showLesson'])->name('lessons.show');
        Route::put('/lessons/{id}',             [\App\Http\Controllers\Admin\LmsController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{id}',          [\App\Http\Controllers\Admin\LmsController::class, 'deleteLesson'])->name('lessons.delete');
        Route::post('/lessons/{id}/complete',   [\App\Http\Controllers\Admin\LmsController::class, 'markLessonComplete'])->name('lessons.complete');
        // Quiz
        Route::get('/courses/{id}/quiz',        [\App\Http\Controllers\Admin\LmsController::class, 'quizBuilder'])->name('quiz.builder');
        Route::post('/courses/{id}/quiz',       [\App\Http\Controllers\Admin\LmsController::class, 'storeQuiz'])->name('quiz.store');
        Route::get('/quiz/{id}/questions',      [\App\Http\Controllers\Admin\LmsController::class, 'quizQuestions'])->name('quiz.questions');
        Route::post('/quiz/{id}/questions',     [\App\Http\Controllers\Admin\LmsController::class, 'storeQuizQuestion'])->name('quiz.question.store');
        Route::delete('/quiz/questions/{id}',   [\App\Http\Controllers\Admin\LmsController::class, 'deleteQuizQuestion'])->name('quiz.question.delete');
        Route::get('/quiz/{id}/attempts',       [\App\Http\Controllers\Admin\LmsController::class, 'quizAttempts'])->name('quiz.attempts');
        // Progress
        Route::get('/courses/{id}/progress',    [\App\Http\Controllers\Admin\LmsController::class, 'progress'])->name('courses.progress');
        // Discussion
        Route::get('/courses/{id}/forum',       [\App\Http\Controllers\Admin\LmsController::class, 'forum'])->name('forum.index');
        Route::post('/courses/{id}/forum',      [\App\Http\Controllers\Admin\LmsController::class, 'storeThread'])->name('forum.thread');
        Route::post('/threads/{id}/reply',      [\App\Http\Controllers\Admin\LmsController::class, 'storeReply'])->name('forum.reply');
        Route::patch('/replies/{id}/answer',    [\App\Http\Controllers\Admin\LmsController::class, 'markAsAnswer'])->name('forum.answer');
        Route::patch('/threads/{id}/hide',      [\App\Http\Controllers\Admin\LmsController::class, 'hideThread'])->name('forum.hide');
        // Assignments
        Route::get('/courses/{id}/assignments', [\App\Http\Controllers\Admin\LmsController::class, 'assignments'])->name('assignments.index');
        Route::post('/courses/{id}/assignments',[\App\Http\Controllers\Admin\LmsController::class, 'storeAssignment'])->name('assignments.store');
        Route::delete('/assignments/{id}',      [\App\Http\Controllers\Admin\LmsController::class, 'deleteAssignment'])->name('assignments.delete');
        Route::get('/assignments/{id}/submissions', [\App\Http\Controllers\Admin\LmsController::class, 'submissions'])->name('assignments.submissions');
        Route::patch('/submissions/{id}/evaluate',  [\App\Http\Controllers\Admin\LmsController::class, 'evaluateSubmission'])->name('assignments.evaluate');
    });
    // ── Module 15 — Events & Calendar ─────────────────────────
    Route::middleware('permission:view events')->prefix('events')->name('events.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\EventController::class, 'index'])->name('index');
        Route::get('/calendar',      [\App\Http\Controllers\Admin\EventController::class, 'calendar'])->name('calendar');
        Route::get('/create',        [\App\Http\Controllers\Admin\EventController::class, 'create'])->name('create');
        Route::post('/',             [\App\Http\Controllers\Admin\EventController::class, 'store'])->name('store');
        Route::get('/{id}',          [\App\Http\Controllers\Admin\EventController::class, 'show'])->name('show');
        Route::get('/{id}/edit',     [\App\Http\Controllers\Admin\EventController::class, 'edit'])->name('edit');
        Route::put('/{id}',          [\App\Http\Controllers\Admin\EventController::class, 'update'])->name('update');
        Route::delete('/{id}',       [\App\Http\Controllers\Admin\EventController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/rsvp',    [\App\Http\Controllers\Admin\EventController::class, 'rsvp'])->name('rsvp');
        Route::post('/{id}/photos',  [\App\Http\Controllers\Admin\EventController::class, 'uploadPhotos'])->name('photos.upload');
        Route::delete('/photos/{photoId}', [\App\Http\Controllers\Admin\EventController::class, 'deletePhoto'])->name('photos.delete');
    });

    // ── Module 15b — Official Circulars & Orders ────────────────
    Route::prefix('circulars')->name('circulars.')->group(function () {
        Route::get('/',                           [\App\Http\Controllers\Admin\CircularOrderController::class, 'index'])->name('index');
        Route::get('/create',                     [\App\Http\Controllers\Admin\CircularOrderController::class, 'create'])->name('create');
        Route::post('/',                          [\App\Http\Controllers\Admin\CircularOrderController::class, 'store'])->name('store');
        Route::get('/{id}',                       [\App\Http\Controllers\Admin\CircularOrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit',                  [\App\Http\Controllers\Admin\CircularOrderController::class, 'edit'])->name('edit');
        Route::put('/{id}',                       [\App\Http\Controllers\Admin\CircularOrderController::class, 'update'])->name('update');
        Route::delete('/{id}',                    [\App\Http\Controllers\Admin\CircularOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/acknowledge',          [\App\Http\Controllers\Admin\CircularOrderController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{id}/pin',                  [\App\Http\Controllers\Admin\CircularOrderController::class, 'togglePin'])->name('pin');
    });
    Route::get('/events/circulars', fn() => redirect()->route('circulars.index'));

    // ── Module 16 — Gate & Visitor Management ─────────────────
    Route::prefix('gate')->name('gate.')->group(function () {
        Route::middleware('permission:view gate')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Admin\GateController::class, 'index'])->name('index');
            Route::get('/create',        [\App\Http\Controllers\Admin\GateController::class, 'create'])->name('create');
            Route::post('/',             [\App\Http\Controllers\Admin\GateController::class, 'store'])->name('store');
            Route::get('/scanner',       [\App\Http\Controllers\Admin\GateController::class, 'scanner'])->name('scanner');
            Route::post('/scan-checkout',[\App\Http\Controllers\Admin\GateController::class, 'scanCheckout'])->name('scan-checkout');
            Route::get('/lookup/students',[\App\Http\Controllers\Admin\GateController::class, 'lookupStudents'])->name('lookup.students');
            Route::get('/lookup/hosts',  [\App\Http\Controllers\Admin\GateController::class, 'lookupHosts'])->name('lookup.hosts');
            Route::get('/report',        [\App\Http\Controllers\Admin\GateController::class, 'report'])->name('report');
            Route::get('/blacklist',     [\App\Http\Controllers\Admin\GateController::class, 'blacklist'])->name('blacklist');
            Route::post('/blacklist',    [\App\Http\Controllers\Admin\GateController::class, 'storeBlacklist'])->name('blacklist.store');
            Route::patch('/blacklist/{id}/remove', [\App\Http\Controllers\Admin\GateController::class, 'removeBlacklist'])->name('blacklist.remove');
            Route::get('/outpass',       [\App\Http\Controllers\Admin\GateController::class, 'outpass'])->name('outpass');
            Route::get('/outpass/create',[\App\Http\Controllers\Admin\GateController::class, 'createOutpass'])->name('outpass.create');
            Route::post('/outpass',      [\App\Http\Controllers\Admin\GateController::class, 'storeOutpass'])->name('outpass.store');
            Route::patch('/outpass/{id}/return', [\App\Http\Controllers\Admin\GateController::class, 'returnOutpass'])->name('outpass.return');
            Route::get('/{id}',          [\App\Http\Controllers\Admin\GateController::class, 'show'])->name('show');
            Route::get('/{id}/pass',     [\App\Http\Controllers\Admin\GateController::class, 'pass'])->name('pass');
            Route::patch('/{id}/checkout',[\App\Http\Controllers\Admin\GateController::class, 'checkout'])->name('checkout');
        });

        // Host Approval & Rejection endpoints (accessible by authenticated staff & gatekeepers)
        Route::middleware('auth')->group(function () {
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\GateController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject',  [\App\Http\Controllers\Admin\GateController::class, 'reject'])->name('reject');
        });
    });

    // ── Module 18 — Alumni Management ─────────────────────────
    Route::middleware('permission:view alumni')->prefix('alumni')->name('alumni.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\AlumniController::class, 'index'])->name('index');
        Route::get('/directory',   [\App\Http\Controllers\Admin\AlumniController::class, 'directory'])->name('directory')->withoutMiddleware('auth');
        Route::get('/create',      [\App\Http\Controllers\Admin\AlumniController::class, 'create'])->name('create');
        Route::post('/',           [\App\Http\Controllers\Admin\AlumniController::class, 'store'])->name('store');
        Route::get('/{id}',        [\App\Http\Controllers\Admin\AlumniController::class, 'show'])->name('show');
        Route::get('/{id}/edit',   [\App\Http\Controllers\Admin\AlumniController::class, 'edit'])->name('edit');
        Route::put('/{id}',        [\App\Http\Controllers\Admin\AlumniController::class, 'update'])->name('update');
        Route::delete('/{id}',     [\App\Http\Controllers\Admin\AlumniController::class, 'destroy'])->name('destroy');
        // Events
        Route::get('/events',              [\App\Http\Controllers\Admin\AlumniController::class, 'events'])->name('events');
        Route::post('/events',             [\App\Http\Controllers\Admin\AlumniController::class, 'eventStore'])->name('events.store');
        Route::post('/events/{id}/toggle', [\App\Http\Controllers\Admin\AlumniController::class, 'eventTogglePublish'])->name('events.toggle');
        Route::delete('/events/{id}',      [\App\Http\Controllers\Admin\AlumniController::class, 'eventDestroy'])->name('events.destroy');
    });

    // ── Module 20 — System Administration ─────────────────────
    Route::middleware('permission:manage settings')->prefix('system')->name('system.')->group(function () {
        Route::get('/audit-log',   [\App\Http\Controllers\Admin\SystemAdminController::class, 'auditLog'])->name('audit-log');
        Route::get('/roles',       [\App\Http\Controllers\Admin\SystemAdminController::class, 'roles'])->name('roles');
        Route::post('/roles',      [\App\Http\Controllers\Admin\SystemAdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{id}',  [\App\Http\Controllers\Admin\SystemAdminController::class, 'updateRole'])->name('roles.update');
        Route::post('/roles/assign',[\App\Http\Controllers\Admin\SystemAdminController::class, 'assignRole'])->name('roles.assign');
        Route::post('/roles/remove',[\App\Http\Controllers\Admin\SystemAdminController::class, 'removeRole'])->name('roles.remove');
        Route::get('/security',    [\App\Http\Controllers\Admin\SystemAdminController::class, 'securitySettings'])->name('security');
        Route::post('/whitelist',  [\App\Http\Controllers\Admin\SystemAdminController::class, 'addIpWhitelist'])->name('whitelist.add');
        Route::delete('/whitelist/{id}', [\App\Http\Controllers\Admin\SystemAdminController::class, 'removeIpWhitelist'])->name('whitelist.remove');
        Route::get('/backup',      [\App\Http\Controllers\Admin\SystemAdminController::class, 'backupInfo'])->name('backup');
        Route::post('/backup',     [\App\Http\Controllers\Admin\SystemAdminController::class, 'triggerBackup'])->name('backup.trigger');
    });

    Route::middleware('permission:view reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
        Route::get('/fee',                [\App\Http\Controllers\Admin\ReportsController::class, 'feeReport'])->name('fee');
        Route::get('/fees',               fn() => redirect()->route('reports.fee'))->name('fees');
        Route::get('/attendance',         [\App\Http\Controllers\Admin\ReportsController::class, 'attendanceReport'])->name('attendance');
        Route::get('/fee/excel',          [\App\Http\Controllers\Admin\ReportsController::class, 'feeReportExcel'])->name('fee.excel');
        Route::get('/attendance/excel',   [\App\Http\Controllers\Admin\ReportsController::class, 'attendanceReportExcel'])->name('attendance.excel');
    });

    // ── Warehouse Module ─────────────────────────────────────────
    Route::middleware('permission:view inventory')->prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\WarehouseController::class, 'index'])->name('index');
        Route::post('/items',               [\App\Http\Controllers\Admin\WarehouseController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{id}',           [\App\Http\Controllers\Admin\WarehouseController::class, 'updateItem'])->name('items.update');
        Route::post('/stock-in',            [\App\Http\Controllers\Admin\WarehouseController::class, 'stockIn'])->name('stock-in');
        Route::post('/stock-out',           [\App\Http\Controllers\Admin\WarehouseController::class, 'stockOut'])->name('stock-out');
        Route::post('/adjustment',          [\App\Http\Controllers\Admin\WarehouseController::class, 'adjustment'])->name('adjustment');
        Route::get('/items/{id}',           [\App\Http\Controllers\Admin\WarehouseController::class, 'showItem'])->name('show-item');
        Route::get('/transactions',         [\App\Http\Controllers\Admin\WarehouseController::class, 'transactions'])->name('transactions');
        Route::get('/admission-kit-config', [\App\Http\Controllers\Admin\WarehouseController::class, 'admissionKitConfig'])->name('admission-kit-config');
        Route::post('/admission-kit-config',[\App\Http\Controllers\Admin\WarehouseController::class, 'saveKitConfig'])->name('admission-kit-config.save');
        Route::get('/reports',              [\App\Http\Controllers\Admin\WarehouseController::class, 'reports'])->name('reports');
        Route::get('/invoice/{id}/download',[\App\Http\Controllers\Admin\WarehouseController::class, 'downloadInvoice'])->name('download-invoice');
    });

    // API Helper for Admission Form Inventory Kit
    Route::get('/api/warehouse/kit/{classId}', [\App\Http\Controllers\Admin\WarehouseController::class, 'getKitForClass'])->name('api.warehouse.kit');

    // ── Module 14 — Inventory & Store Management (Backwards Compatibility) ────
    Route::middleware('permission:view inventory')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Admin\WarehouseController::class, 'index'])->name('index');
        Route::get('/items/create',       [\App\Http\Controllers\Admin\InventoryController::class, 'createItem'])->name('items.create');
        Route::post('/items',             [\App\Http\Controllers\Admin\InventoryController::class, 'storeItem'])->name('items.store');
        Route::get('/items/{id}/edit',    [\App\Http\Controllers\Admin\InventoryController::class, 'editItem'])->name('items.edit');
        Route::put('/items/{id}',         [\App\Http\Controllers\Admin\InventoryController::class, 'updateItem'])->name('items.update');
        Route::get('/categories',         [\App\Http\Controllers\Admin\InventoryController::class, 'categories'])->name('categories');
        Route::post('/categories',        [\App\Http\Controllers\Admin\InventoryController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{id}',    [\App\Http\Controllers\Admin\InventoryController::class, 'updateCategory'])->name('categories.update');
        Route::get('/vendors',            [\App\Http\Controllers\Admin\InventoryController::class, 'vendors'])->name('vendors');
        Route::post('/vendors',           [\App\Http\Controllers\Admin\InventoryController::class, 'storeVendor'])->name('vendors.store');
        Route::put('/vendors/{id}',       [\App\Http\Controllers\Admin\InventoryController::class, 'updateVendor'])->name('vendors.update');
        Route::get('/requisitions',       [\App\Http\Controllers\Admin\InventoryController::class, 'requisitions'])->name('requisitions');
        Route::get('/requisitions/create',[\App\Http\Controllers\Admin\InventoryController::class, 'createRequisition'])->name('requisitions.create');
        Route::post('/requisitions',      [\App\Http\Controllers\Admin\InventoryController::class, 'storeRequisition'])->name('requisitions.store');
        Route::patch('/requisitions/{id}/approve', [\App\Http\Controllers\Admin\InventoryController::class, 'approveRequisition'])->name('requisitions.approve');
        Route::get('/requisitions/{id}/print',   [\App\Http\Controllers\Admin\InventoryController::class, 'printRequisition'])->name('requisitions.print');
        Route::get('/purchase-orders',        [\App\Http\Controllers\Admin\InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/purchase-orders/{id}/print', [\App\Http\Controllers\Admin\InventoryController::class, 'printPurchaseOrder'])->name('purchase-orders.print');
        Route::get('/purchase-orders/create', [\App\Http\Controllers\Admin\InventoryController::class, 'createPurchaseOrder'])->name('purchase-orders.create');
        Route::post('/purchase-orders',       [\App\Http\Controllers\Admin\InventoryController::class, 'storePurchaseOrder'])->name('purchase-orders.store');
        Route::patch('/purchase-orders/{id}/mark-sent', [\App\Http\Controllers\Admin\InventoryController::class, 'markPoSent'])->name('purchase-orders.mark-sent');
        Route::get('/grn',                [\App\Http\Controllers\Admin\InventoryController::class, 'grn'])->name('grn');
        Route::get('/grn/create',         [\App\Http\Controllers\Admin\InventoryController::class, 'createGrn'])->name('grn.create');
        Route::post('/grn',               [\App\Http\Controllers\Admin\InventoryController::class, 'storeGrn'])->name('grn.store');
        Route::get('/issuances',          [\App\Http\Controllers\Admin\InventoryController::class, 'issuances'])->name('issuances');
        Route::get('/issuances/create',   [\App\Http\Controllers\Admin\InventoryController::class, 'createIssuance'])->name('issuances.create');
        Route::post('/issuances',         [\App\Http\Controllers\Admin\InventoryController::class, 'storeIssuance'])->name('issuances.store');
        Route::get('/stock-report',       [\App\Http\Controllers\Admin\InventoryController::class, 'stockReport'])->name('stock-report');
    });
    Route::get('/settings',                           [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings',                          [App\Http\Controllers\Admin\SettingsController::class, 'save'])->name('settings.save');
    Route::post('/settings/upload-logo',              [App\Http\Controllers\Admin\SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
    Route::delete('/settings/delete-logo/{slot}',     [App\Http\Controllers\Admin\SettingsController::class, 'deleteLogo'])->name('settings.delete-logo');
    Route::post('/settings/upload-seal',              [App\Http\Controllers\Admin\SettingsController::class, 'uploadSeal'])->name('settings.upload-seal');
    Route::delete('/settings/delete-seal/{slot}',     [App\Http\Controllers\Admin\SettingsController::class, 'deleteSeal'])->name('settings.delete-seal');
    Route::post('/settings/save-seal-label',          [App\Http\Controllers\Admin\SettingsController::class, 'saveSealLabel'])->name('settings.save-seal-label');
    Route::post('/settings/upload-signature',         [App\Http\Controllers\Admin\SettingsController::class, 'uploadSignature'])->name('settings.upload-signature');
    Route::post('/settings/upload-stamp',             [App\Http\Controllers\Admin\SettingsController::class, 'uploadStamp'])->name('settings.upload-stamp');
    Route::post('/settings/reset-theme',              [App\Http\Controllers\Admin\SettingsController::class, 'resetTheme'])->name('settings.reset-theme');
    Route::delete('/settings/delete-signature',       [App\Http\Controllers\Admin\SettingsController::class, 'deleteSignature'])->name('settings.delete-signature');
    Route::delete('/settings/delete-stamp',           [App\Http\Controllers\Admin\SettingsController::class, 'deleteStamp'])->name('settings.delete-stamp');
    Route::get('/settings/notification-templates',    [App\Http\Controllers\Admin\SettingsController::class, 'notificationTemplates'])->name('settings.notification-templates');
    Route::post('/settings/notification-templates',   [App\Http\Controllers\Admin\SettingsController::class, 'saveNotificationTemplate'])->name('settings.notification-templates.save');

    // ── Books & Notes Management (Settings → Books & Notes) ──────
    Route::get('/settings/books-notes', [App\Http\Controllers\Admin\BooksNotesController::class, 'index'])->name('settings.books-notes.index');
    Route::get('/settings/books-notes/slip/{studentId}', [App\Http\Controllers\Admin\BooksNotesController::class, 'slip'])->name('settings.books-notes.slip');
    Route::prefix('api/books-notes')->name('api.books-notes.')->group(function () {
        Route::get('/cards',                           [App\Http\Controllers\Admin\BooksNotesController::class, 'apiCards'])->name('cards');
        Route::get('/checklist',                       [App\Http\Controllers\Admin\BooksNotesController::class, 'apiChecklist'])->name('checklist');
        Route::get('/distribution/summary',            [App\Http\Controllers\Admin\BooksNotesController::class, 'apiDistributionSummary'])->name('distribution.summary');
        Route::get('/distribution/students',           [App\Http\Controllers\Admin\BooksNotesController::class, 'apiDistributionStudents'])->name('distribution.students');
        Route::get('/distribution/search-students',    [App\Http\Controllers\Admin\BooksNotesController::class, 'apiSearchStudents'])->name('distribution.search-students');
        Route::get('/distribution/student/{studentId}',[App\Http\Controllers\Admin\BooksNotesController::class, 'apiStudentChecklist'])->name('distribution.student');
        Route::post('/distribution/issue-item',        [App\Http\Controllers\Admin\BooksNotesController::class, 'apiIssueItem'])->name('distribution.issue-item');
        Route::post('/distribution/issue-all/{studentId}', [App\Http\Controllers\Admin\BooksNotesController::class, 'apiIssueAll'])->name('distribution.issue-all');
        Route::get('/',                                [App\Http\Controllers\Admin\BooksNotesController::class, 'apiList'])->name('index');
        Route::post('/',                               [App\Http\Controllers\Admin\BooksNotesController::class, 'apiStore'])->name('store');
        Route::get('/{id}',                            [App\Http\Controllers\Admin\BooksNotesController::class, 'apiShow'])->name('show');
        Route::put('/{id}',                            [App\Http\Controllers\Admin\BooksNotesController::class, 'apiUpdate'])->name('update');
        Route::delete('/{id}',                         [App\Http\Controllers\Admin\BooksNotesController::class, 'apiDestroy'])->name('destroy');
        Route::patch('/{id}/status',                   [App\Http\Controllers\Admin\BooksNotesController::class, 'apiToggleStatus'])->name('status');
    });
    Route::get('/api/academic-groups', [App\Http\Controllers\Admin\BooksNotesController::class, 'apiGroups'])->name('api.academic-groups');

    // API helpers
    Route::get('/api/sections', function (\Illuminate\Http\Request $r) {
        $sections = \App\Models\Section::where('class_id', $r->class_id)
            ->where(function ($q) { $q->whereNull('academic_year_id')->orWhere('academic_year_id', \App\Models\AcademicYear::current()?->id); })
            ->get(['id', 'name']);
        return response()->json($sections);
    })->name('api.sections');

    // ── Unified Activity Hub Module (Notices, Events, Meetings, Tasks, Calendar, Approvals) ──
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/my-day',     [\App\Http\Controllers\Admin\ActivityHubController::class, 'myDay'])->name('my-day');
        Route::get('/calendar',   [\App\Http\Controllers\Admin\ActivityHubController::class, 'calendar'])->name('calendar');
        Route::get('/approvals',  [\App\Http\Controllers\Admin\ActivityHubController::class, 'approvals'])->name('approvals');
        Route::get('/reports',    [\App\Http\Controllers\Admin\ActivityHubController::class, 'reports'])->name('reports');

        // Notices
        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\ActivityHubController::class, 'notices'])->name('index');
            Route::get('/create',  [\App\Http\Controllers\Admin\ActivityHubController::class, 'createNotice'])->name('create');
            Route::get('/{id}',    [\App\Http\Controllers\Admin\ActivityHubController::class, 'showNotice'])->name('show');
        });

        // Events
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\ActivityHubController::class, 'events'])->name('index');
            Route::get('/create',  [\App\Http\Controllers\Admin\ActivityHubController::class, 'createEvent'])->name('create');
            Route::get('/{id}',    [\App\Http\Controllers\Admin\ActivityHubController::class, 'showEvent'])->name('show');
        });

        // Meetings
        Route::prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\ActivityHubController::class, 'meetings'])->name('index');
            Route::get('/create',  [\App\Http\Controllers\Admin\ActivityHubController::class, 'createMeeting'])->name('create');
            Route::get('/{id}',    [\App\Http\Controllers\Admin\ActivityHubController::class, 'showMeeting'])->name('show');
        });

        // Tasks
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/',        [\App\Http\Controllers\Admin\ActivityHubController::class, 'tasks'])->name('index');
            Route::get('/create',  [\App\Http\Controllers\Admin\ActivityHubController::class, 'createTask'])->name('create');
            Route::get('/{id}',    [\App\Http\Controllers\Admin\ActivityHubController::class, 'showTask'])->name('show');
        });
    });

    // ── Activity Hub REST APIs ────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        // Notices API
        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\NoticeApiController::class, 'index'])->name('index');
            Route::post('/',                       [\App\Http\Controllers\Api\NoticeApiController::class, 'store'])->name('store');
            Route::get('/{id}',                    [\App\Http\Controllers\Api\NoticeApiController::class, 'show'])->name('show');
            Route::patch('/{id}',                  [\App\Http\Controllers\Api\NoticeApiController::class, 'update'])->name('update');
            Route::post('/{id}/submit',            [\App\Http\Controllers\Api\NoticeApiController::class, 'submitForApproval'])->name('submit');
            Route::post('/{id}/approve',           [\App\Http\Controllers\Api\NoticeApiController::class, 'approve'])->name('approve');
            Route::post('/{id}/publish',           [\App\Http\Controllers\Api\NoticeApiController::class, 'publish'])->name('publish');
            Route::post('/{id}/acknowledge',       [\App\Http\Controllers\Api\NoticeApiController::class, 'acknowledge'])->name('acknowledge');
            Route::get('/{id}/acknowledgements',   [\App\Http\Controllers\Api\NoticeApiController::class, 'acknowledgements'])->name('acknowledgements');
        });

        // Circulars & Official Orders API
        Route::prefix('circulars')->name('circulars.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\CircularApiController::class, 'index'])->name('index');
            Route::post('/',                       [\App\Http\Controllers\Api\CircularApiController::class, 'store'])->name('store');
            Route::get('/{id}',                    [\App\Http\Controllers\Api\CircularApiController::class, 'show'])->name('show');
            Route::post('/{id}/acknowledge',       [\App\Http\Controllers\Api\CircularApiController::class, 'acknowledge'])->name('acknowledge');
            Route::delete('/{id}',                 [\App\Http\Controllers\Api\CircularApiController::class, 'destroy'])->name('destroy');
        });

        // Events API
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\EventApiController::class, 'index'])->name('index');
            Route::post('/',                       [\App\Http\Controllers\Api\EventApiController::class, 'store'])->name('store');
            Route::get('/{id}',                    [\App\Http\Controllers\Api\EventApiController::class, 'show'])->name('show');
            Route::patch('/{id}',                  [\App\Http\Controllers\Api\EventApiController::class, 'update'])->name('update');
            Route::post('/{id}/approve',           [\App\Http\Controllers\Api\EventApiController::class, 'approve'])->name('approve');
            Route::post('/{id}/participants',      [\App\Http\Controllers\Api\EventApiController::class, 'addParticipants'])->name('participants');
            Route::post('/{id}/attendance',        [\App\Http\Controllers\Api\EventApiController::class, 'recordAttendance'])->name('attendance');
            Route::post('/{id}/complete',          [\App\Http\Controllers\Api\EventApiController::class, 'complete'])->name('complete');
            Route::delete('/{id}',                [\App\Http\Controllers\Api\EventApiController::class, 'destroy'])->name('destroy');
        });

        // Meetings API
        Route::prefix('meetings')->name('meetings.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\MeetingApiController::class, 'index'])->name('index');
            Route::post('/',                       [\App\Http\Controllers\Api\MeetingApiController::class, 'store'])->name('store');
            Route::get('/{id}',                    [\App\Http\Controllers\Api\MeetingApiController::class, 'show'])->name('show');
            Route::patch('/{id}',                  [\App\Http\Controllers\Api\MeetingApiController::class, 'update'])->name('update');
            Route::post('/{id}/rsvp',              [\App\Http\Controllers\Api\MeetingApiController::class, 'rsvp'])->name('rsvp');
            Route::post('/{id}/attendance',        [\App\Http\Controllers\Api\MeetingApiController::class, 'recordAttendance'])->name('attendance');
            Route::post('/{id}/minutes',           [\App\Http\Controllers\Api\MeetingApiController::class, 'recordMinutes'])->name('minutes');
            Route::post('/{id}/action-items',      [\App\Http\Controllers\Api\MeetingApiController::class, 'createActionItems'])->name('action-items');
        });

        // Tasks API
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\TaskApiController::class, 'index'])->name('index');
            Route::post('/',                       [\App\Http\Controllers\Api\TaskApiController::class, 'store'])->name('store');
            Route::get('/{id}',                    [\App\Http\Controllers\Api\TaskApiController::class, 'show'])->name('show');
            Route::patch('/{id}',                  [\App\Http\Controllers\Api\TaskApiController::class, 'update'])->name('update');
            Route::post('/{id}/updates',           [\App\Http\Controllers\Api\TaskApiController::class, 'postUpdate'])->name('updates');
            Route::post('/{id}/submit',            [\App\Http\Controllers\Api\TaskApiController::class, 'submitForReview'])->name('submit');
            Route::post('/{id}/review',            [\App\Http\Controllers\Api\TaskApiController::class, 'review'])->name('review');
            Route::post('/{id}/reassign',          [\App\Http\Controllers\Api\TaskApiController::class, 'reassign'])->name('reassign');
        });

        // Calendar API
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/activities',              [\App\Http\Controllers\Api\CalendarApiController::class, 'activities'])->name('activities');
            Route::get('/conflicts',               [\App\Http\Controllers\Api\CalendarApiController::class, 'conflicts'])->name('conflicts');
        });

        // Notifications API
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/',                        [\App\Http\Controllers\Api\NotificationApiController::class, 'index'])->name('index');
            Route::get('/unread-count',            [\App\Http\Controllers\Api\NotificationApiController::class, 'unreadCount'])->name('unread-count');
            Route::patch('/{id}/read',             [\App\Http\Controllers\Api\NotificationApiController::class, 'markAsRead'])->name('read');
            Route::post('/mark-all-read',          [\App\Http\Controllers\Api\NotificationApiController::class, 'markAllAsRead'])->name('mark-all-read');
        });

        // Dashboard API
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/my-activities',           [\App\Http\Controllers\Api\DashboardApiController::class, 'myActivities'])->name('my-activities');
            Route::get('/management-summary',      [\App\Http\Controllers\Api\DashboardApiController::class, 'managementSummary'])->name('management-summary');
        });
    });
});

// ── Module 11 — Parent & Student Portal ───────────────────────
Route::get('/portal', fn() => redirect('/portal/parent/dashboard'))->name('portal.root');
Route::get('/portal/login', fn() => redirect('/login'))->name('portal.login');

// Separate auth group — parent/student roles, no admin middleware
Route::middleware(['auth', 'portal.access'])->prefix('portal')->name('portal.')->group(function () {

    // Parent portal
    Route::middleware([\Spatie\Permission\Middleware\RoleMiddleware::using('parent')])->prefix('parent')->name('parent.')->group(function () {
        Route::get('/dashboard',   [\App\Http\Controllers\Portal\PortalController::class, 'parentDashboard'])->name('dashboard');
        Route::get('/attendance',  [\App\Http\Controllers\Portal\PortalController::class, 'parentAttendance'])->name('attendance');
        Route::get('/fees',                        [\App\Http\Controllers\Portal\PortalController::class, 'parentFees'])->name('fees');
        Route::get('/fees/pdf',                    [\App\Http\Controllers\Portal\PortalController::class, 'parentFeePdf'])->name('fees.pdf');
        Route::get('/fees/receipt/{id}',           [\App\Http\Controllers\Portal\PortalController::class, 'parentPaymentReceipt'])->name('fees.receipt');
        Route::get('/exams',                       [\App\Http\Controllers\Portal\PortalController::class, 'parentExams'])->name('exams');
        Route::get('/notices',     [\App\Http\Controllers\Portal\PortalController::class, 'parentNotices'])->name('notices');
        Route::get('/profile',     [\App\Http\Controllers\Portal\PortalController::class, 'parentProfile'])->name('profile');
        Route::put('/profile',     [\App\Http\Controllers\Portal\PortalController::class, 'updateParentProfile'])->name('profile.update');
    });

    // Student portal
    Route::middleware([\Spatie\Permission\Middleware\RoleMiddleware::using('student')])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard',   [\App\Http\Controllers\Portal\PortalController::class, 'studentDashboard'])->name('dashboard');
        Route::get('/attendance',  [\App\Http\Controllers\Portal\PortalController::class, 'studentAttendance'])->name('attendance');
        Route::get('/fees',                        [\App\Http\Controllers\Portal\PortalController::class, 'studentFees'])->name('fees');
        Route::get('/fees/pdf',                    [\App\Http\Controllers\Portal\PortalController::class, 'studentFeePdf'])->name('fees.pdf');
        Route::get('/fees/receipt/{id}',           [\App\Http\Controllers\Portal\PortalController::class, 'studentPaymentReceipt'])->name('fees.receipt');
        Route::get('/exams',                       [\App\Http\Controllers\Portal\PortalController::class, 'studentExams'])->name('exams');
        Route::get('/exams/hall-ticket',           [\App\Http\Controllers\Portal\PortalController::class, 'studentHallTicket'])->name('exams.hall-ticket');
        Route::get('/report-card/pdf',             [\App\Http\Controllers\Portal\PortalController::class, 'studentReportPdf'])->name('report.pdf');
        Route::get('/timetable',   [\App\Http\Controllers\Portal\PortalController::class, 'studentTimetable'])->name('timetable');
        Route::get('/academics',   [\App\Http\Controllers\Portal\PortalController::class, 'studentAcademics'])->name('academics');
        Route::get('/courses/{id}',[\App\Http\Controllers\Portal\PortalController::class, 'studentCourse'])->name('course');
        Route::get('/notices',     [\App\Http\Controllers\Portal\PortalController::class, 'studentNotices'])->name('notices');
        Route::get('/profile',     [\App\Http\Controllers\Portal\PortalController::class, 'studentProfile'])->name('profile');
        Route::put('/profile',     [\App\Http\Controllers\Portal\PortalController::class, 'updateStudentProfile'])->name('profile.update');
    });

    // Shared (parent + student)
    Route::put('/change-password', [\App\Http\Controllers\Portal\PortalController::class, 'changePassword'])->name('change-password');
    Route::post('/leave/submit',    [\App\Http\Controllers\Portal\PortalController::class, 'submitLeave'])->name('leave.submit');
    Route::post('/academics/submit-assignment', [\App\Http\Controllers\Portal\PortalController::class, 'submitAssignment'])->name('academics.submit-assignment');
    Route::get('/quiz/{quiz}',                  [\App\Http\Controllers\Portal\PortalController::class, 'takeQuiz'])->name('quiz.take');
    Route::post('/quiz/{quiz}/submit',          [\App\Http\Controllers\Portal\PortalController::class, 'submitQuiz'])->name('quiz.submit');
    Route::get('/quiz/result/{attempt}',        [\App\Http\Controllers\Portal\PortalController::class, 'quizResult'])->name('quiz.result');
    Route::post('/notice/{id}/read',[\App\Http\Controllers\Portal\PortalController::class, 'markNoticeRead'])->name('notice.read');
    Route::post('/notices/mark-all-read', [\App\Http\Controllers\Portal\PortalController::class, 'markAllNoticesRead'])->name('notices.mark-all-read');
    Route::get('/notifications',    [\App\Http\Controllers\Portal\PortalController::class, 'notifications'])->name('notifications');
});
