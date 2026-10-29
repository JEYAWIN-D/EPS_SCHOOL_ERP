<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\IdCardAuditLog;
use App\Models\IdCardGenerationJob;
use App\Models\IdCardTemplate;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Visitor;
use App\Services\IdCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdCardController extends Controller
{
    protected IdCardService $idCardService;

    public function __construct(IdCardService $idCardService)
    {
        $this->idCardService = $idCardService;
    }

    /* ═════════════════════════════════════════════════════════════════════════
       1. STUDENT ID CARD MANAGEMENT
       ═════════════════════════════════════════════════════════════════════════ */

    /**
     * Student ID Card Management index page.
     */
    public function studentIndex(Request $request)
    {
        $this->idCardService->ensureDefaultTemplates();

        $currentYear = AcademicYear::current();
        $classes = Classes::active()->get();
        $classFilter = $request->get('class_id');
        $sectionFilter = $request->get('section_id');
        $statusFilter = $request->get('card_status', 'all');
        $search = $request->get('search');
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';

        $sections = $classFilter ? Section::where('class_id', $classFilter)->orderBy('name')->get() : collect();
        $school = SchoolSetting::first();

        // Base query for active enrollments
        $query = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id));

        // Enforce teacher scope if scoped
        if ($request->is_scoped) {
            if (!empty($request->scope_student_ids)) {
                $query->whereIn('student_id', $request->scope_student_ids);
            } elseif (!empty($request->scope_class_ids)) {
                $query->whereIn('class_id', $request->scope_class_ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($classFilter) $query->where('class_id', $classFilter);
        if ($sectionFilter) $query->where('section_id', $sectionFilter);

        if ($search) {
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where(function ($q) use ($search) {
                    $q->where('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%")
                      ->orWhere('admission_no', 'ilike', "%{$search}%")
                      ->orWhere('roll_number', 'ilike', "%{$search}%");
                });
            });
        }

        $allEnrollments = $query->get();

        // Fetch existing ID cards for these students
        $studentIds = $allEnrollments->pluck('student_id')->toArray();
        $existingCards = IdCard::where('card_type', IdCard::TYPE_STUDENT)
            ->whereIn('linked_entity_id', $studentIds)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get()
            ->keyBy('linked_entity_id');

        // Attach card and compute statistics
        $missingPhotosCount = 0;
        $totalActiveCards = 0;
        $totalPendingCards = 0;

        foreach ($allEnrollments as $enrollment) {
            $student = $enrollment->student;
            $card = $existingCards->get($student->id);
            $enrollment->_card = $card;

            if (empty($student->photo)) {
                $missingPhotosCount++;
            }

            if ($card && in_array($card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED])) {
                $totalActiveCards++;
            } else {
                $totalPendingCards++;
            }
        }

        // Apply card_status filter
        $filteredEnrollments = $allEnrollments;
        if ($statusFilter === 'active') {
            $filteredEnrollments = $allEnrollments->filter(fn($e) => $e->_card && in_array($e->_card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED]));
        } elseif ($statusFilter === 'pending') {
            $filteredEnrollments = $allEnrollments->filter(fn($e) => !$e->_card || !in_array($e->_card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED]));
        } elseif ($statusFilter === 'missing_photo') {
            $filteredEnrollments = $allEnrollments->filter(fn($e) => empty($e->student->photo));
        }

        // Active template for student cards
        $activeTemplate = IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first() ?? IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)->first();

        $targetClass = $classFilter ? Classes::find($classFilter) : null;
        $targetSection = $sectionFilter ? Section::find($sectionFilter) : null;

        return view('students.id-cards-index', compact(
            'classes', 'sections', 'filteredEnrollments', 'allEnrollments',
            'currentYear', 'school', 'classFilter', 'sectionFilter', 'statusFilter',
            'search', 'orientation', 'side', 'targetClass', 'targetSection',
            'missingPhotosCount', 'totalActiveCards', 'totalPendingCards', 'activeTemplate'
        ));
    }

    /**
     * Preview single student ID card with full interactive controls.
     */
    public function singleStudent(Request $request, int $id)
    {
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($id);
        $school = SchoolSetting::first();
        $currentYear = AcademicYear::current();
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';

        $card = $this->idCardService->getOrCreateCardForStudent($student, null, $orientation, $side);

        $template = $card->template ?? IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('student', $orientation);

        // Generate Barcode SVG for linear scanning
        $rollNo = $student->roll_number ?: str_pad($student->id % 100 ?: 1, 2, '0', STR_PAD_LEFT);
        $barcodeVal = $card->barcode_value ?: ('EPS-R' . str_pad($rollNo, 2, '0', STR_PAD_LEFT));
        $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 32, 2, '#0f172a');

        // Audit logs for this card
        $auditLogs = $card->auditLogs()->with('performer')->get();

        $verifyUrl = $card->getQrVerificationUrl();

        return view('students.single-id-card-view', compact(
            'student', 'card', 'school', 'currentYear', 'orientation', 'side',
            'template', 'config', 'barcodeVal', 'barcodeSvg', 'auditLogs', 'verifyUrl'
        ));
    }

    /**
     * Bulk or Class-wise Student ID card PDF download (Arranged in A4 Grid).
     */
    public function bulkDownloadStudentPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(240);

        $currentYear = AcademicYear::current();
        $classFilter = $request->get('class_id');
        $sectionFilter = $request->get('section_id');
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';
        $studentIds = $request->get('student_ids');

        $query = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('status', 'active')
            ->whereHas('student', fn($sq) => $sq->where('status', 'active'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id));

        if (!empty($studentIds)) {
            $ids = is_array($studentIds) ? $studentIds : explode(',', $studentIds);
            $query->whereIn('student_id', $ids);
        } else {
            if ($classFilter) $query->where('class_id', $classFilter);
            if ($sectionFilter) $query->where('section_id', $sectionFilter);
        }

        $enrollments = $query->get();

        if ($enrollments->isEmpty()) {
            return back()->with('error', 'No eligible student records found for ID card generation.');
        }

        // Active template
        $template = IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first() ?? IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('student', $orientation);
        $school = SchoolSetting::first();

        // Ensure ID card records exist and generate linear barcode SVGs
        $cardsData = [];
        foreach ($enrollments as $e) {
            $student = $e->student;
            $card = $this->idCardService->getOrCreateCardForStudent($student, $template?->id, $orientation, $side);
            $rollNo = $student->roll_number ?: str_pad($student->id % 100 ?: 1, 2, '0', STR_PAD_LEFT);
            $barcodeVal = $card->barcode_value ?: ('EPS-R' . str_pad($rollNo, 2, '0', STR_PAD_LEFT));
            $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 24, 2, '#0f172a');
            $cardsData[] = [
                'enrollment' => $e,
                'student'    => $student,
                'card'       => $card,
                'barcodeVal' => $barcodeVal,
                'barcodeSvg' => $barcodeSvg,
            ];
        }

        $targetClass = $classFilter ? Classes::find($classFilter) : null;
        $targetSection = $sectionFilter ? Section::find($sectionFilter) : null;

        $pdf = Pdf::loadView('pdf.student-id-cards-grid', compact(
            'cardsData', 'school', 'currentYear', 'orientation', 'side', 'config', 'targetClass', 'targetSection'
        ));

        // Use appropriate paper orientation based on card layout
        if ($orientation === 'landscape') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        $classLabel = $targetClass ? ('Class-' . Str::slug($targetClass->name)) : 'Selected-Students';
        if ($targetSection) $classLabel .= '-Sec-' . $targetSection->name;

        // Record generation job
        IdCardGenerationJob::create([
            'job_type'          => 'student_bulk',
            'requested_by'      => Auth::id(),
            'filters_json'      => ['class_id' => $classFilter, 'section_id' => $sectionFilter, 'orientation' => $orientation, 'side' => $side],
            'total_records'     => count($cardsData),
            'processed_records' => count($cardsData),
            'failed_records'    => 0,
            'job_status'        => 'completed',
            'completed_at'      => now(),
        ]);

        return $pdf->download("student-id-cards-{$classLabel}-{$orientation}-{$side}.pdf");
    }

    /* ═════════════════════════════════════════════════════════════════════════
       2. STAFF ID CARD MANAGEMENT
       ═════════════════════════════════════════════════════════════════════════ */

    /**
     * Dedicated Staff ID Card Management submenu page.
     */
    public function staffIndex(Request $request)
    {
        $this->idCardService->ensureDefaultTemplates();

        $deptFilter = $request->get('department_id');
        $statusFilter = $request->get('card_status', 'all');
        $employmentStatus = $request->get('employment_status', 'active'); // active, all, inactive
        $search = $request->get('search');
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';

        $departments = Department::orderBy('name')->get();
        $school = SchoolSetting::first();

        $query = Employee::with('department');

        if ($employmentStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($employmentStatus === 'inactive') {
            $query->where('is_active', false);
        }

        if ($deptFilter) {
            $query->where('department_id', $deptFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('employee_code', 'ilike', "%{$search}%")
                  ->orWhere('designation', 'ilike', "%{$search}%");
            });
        }

        $allEmployees = $query->orderBy('first_name')->get();

        // Existing cards
        $empIds = $allEmployees->pluck('id')->toArray();
        $existingCards = IdCard::where('card_type', IdCard::TYPE_STAFF)
            ->whereIn('linked_entity_id', $empIds)
            ->get()
            ->keyBy('linked_entity_id');

        $missingPhotosCount = 0;
        $totalActiveCards = 0;
        $totalPendingCards = 0;

        foreach ($allEmployees as $emp) {
            $card = $existingCards->get($emp->id);
            $emp->_card = $card;

            if (empty($emp->photo)) {
                $missingPhotosCount++;
            }

            if ($card && in_array($card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED])) {
                $totalActiveCards++;
            } else {
                $totalPendingCards++;
            }
        }

        $filteredEmployees = $allEmployees;
        if ($statusFilter === 'active') {
            $filteredEmployees = $allEmployees->filter(fn($e) => $e->_card && in_array($e->_card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED]));
        } elseif ($statusFilter === 'pending') {
            $filteredEmployees = $allEmployees->filter(fn($e) => !$e->_card || !in_array($e->_card->status, [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED]));
        } elseif ($statusFilter === 'missing_photo') {
            $filteredEmployees = $allEmployees->filter(fn($e) => empty($e->photo));
        }

        $activeTemplate = IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first() ?? IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)->first();

        return view('hr.staff-id-cards-index', compact(
            'departments', 'filteredEmployees', 'allEmployees', 'school',
            'deptFilter', 'statusFilter', 'employmentStatus', 'search',
            'orientation', 'side', 'missingPhotosCount', 'totalActiveCards',
            'totalPendingCards', 'activeTemplate'
        ));
    }

    /**
     * Preview single staff ID card.
     */
    public function singleStaff(Request $request, int $id)
    {
        $employee = Employee::with('department')->findOrFail($id);
        $school = SchoolSetting::first();
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';
        $overrideInactive = $request->boolean('override_inactive', false);

        if (!$employee->is_active && !$overrideInactive) {
            // Still allow preview of existing card if present, otherwise warn
            $card = IdCard::where('linked_entity_type', Employee::class)
                ->where('linked_entity_id', $employee->id)
                ->latest('id')
                ->first();
        } else {
            $card = $this->idCardService->getOrCreateCardForEmployee($employee, null, $orientation, $side, $overrideInactive);
        }

        $template = $card?->template ?? IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('staff', $orientation);

        $barcodeVal = $card ? ($card->barcode_value ?: ('EMP-' . ($employee->employee_code ?: $employee->id))) : ('EMP-' . ($employee->employee_code ?: $employee->id));
        $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 32, 2, '#0f172a');

        $auditLogs = $card ? $card->auditLogs()->with('performer')->get() : collect();
        $verifyUrl = $card?->getQrVerificationUrl();

        return view('hr.single-staff-id-card-view', compact(
            'employee', 'card', 'school', 'orientation', 'side',
            'template', 'config', 'barcodeVal', 'barcodeSvg', 'auditLogs', 'overrideInactive', 'verifyUrl'
        ));
    }

    /**
     * Bulk Staff ID Card PDF download (Arranged in A4 Grid).
     */
    public function bulkDownloadStaffPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $deptFilter = $request->get('department_id');
        $orientation = $request->get('orientation') ?? $request->get('style', 'landscape');
        $orientation = in_array($orientation, ['portrait', 'landscape']) ? $orientation : 'landscape';
        $side = in_array($request->get('side'), ['both', 'front', 'back']) ? $request->get('side') : 'both';
        $staffIds = $request->get('staff_ids') ?? $request->get('ids');
        $category = $request->get('category');
        $search = trim((string) $request->get('search', ''));
        $batch = max(1, (int) $request->get('batch', 1));
        $limit = max(1, min(50, (int) $request->get('limit', 24))); // Default 24 cards per batch to avoid Nginx 504 timeouts

        $query = Employee::with('department')->where('is_active', true);

        if (!empty($staffIds)) {
            $ids = is_array($staffIds) ? $staffIds : explode(',', (string) $staffIds);
            $query->whereIn('id', $ids);
        } else {
            if ($category && $category !== 'all') {
                $query->where('employee_type', $category);
            }
            if ($deptFilter) {
                $query->where('department_id', $deptFilter);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%");
                });
            }
        }

        $totalMatching = (clone $query)->count();
        if ($totalMatching === 0) {
            return back()->with('error', 'No eligible staff records found for ID card printing.');
        }

        // Apply batch pagination if downloading bulk without specific individual IDs
        if (empty($staffIds) && $totalMatching > $limit) {
            $offset = ($batch - 1) * $limit;
            $query->skip($offset)->take($limit);
        }

        $employees = $query->orderBy('first_name')->get();

        $template = IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first() ?? IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('staff', $orientation);
        $school = SchoolSetting::first();

        // Preload existing cards in one bulk query
        $empIds = $employees->pluck('id')->all();
        $existingCards = IdCard::where('linked_entity_type', Employee::class)
            ->whereIn('linked_entity_id', $empIds)
            ->whereIn('status', [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED, IdCard::STATUS_GENERATED])
            ->get()
            ->keyBy('linked_entity_id');

        $cardsData = [];
        foreach ($employees as $emp) {
            $card = $existingCards->get($emp->id);
            if (!$card) {
                $card = $this->idCardService->getOrCreateCardForEmployee($emp, $template?->id, $orientation, $side);
            } elseif ($card->orientation !== $orientation || $card->side !== $side) {
                $card->orientation = $orientation;
                $card->side = $side;
                $card->saveQuietly();
            }

            $barcodeVal = $card->barcode_value ?: ('EMP-' . ($emp->employee_code ?: $emp->id));
            $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 24, 2, '#0f172a');
            $cardsData[] = [
                'employee'   => $emp,
                'card'       => $card,
                'barcodeVal' => $barcodeVal,
                'barcodeSvg' => $barcodeSvg,
            ];
        }

        $pdf = Pdf::loadView('pdf.staff-id-cards-grid', compact(
            'cardsData', 'school', 'orientation', 'side', 'config'
        ));

        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        if ($orientation === 'landscape') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        IdCardGenerationJob::create([
            'job_type'          => 'staff_bulk',
            'requested_by'      => Auth::id(),
            'filters_json'      => [
                'category'      => $category,
                'department_id' => $deptFilter,
                'orientation'   => $orientation,
                'side'          => $side,
                'batch'         => $batch,
            ],
            'total_records'     => count($cardsData),
            'processed_records' => count($cardsData),
            'failed_records'    => 0,
            'job_status'        => 'completed',
            'completed_at'      => now(),
        ]);

        $catLabel = $category ?: 'all-staff';
        $batchLabel = ($totalMatching > $limit && empty($staffIds)) ? "-batch-{$batch}" : '';
        return $pdf->download("staff-id-cards-{$catLabel}-{$orientation}-{$side}{$batchLabel}.pdf");
    }

    /* ═════════════════════════════════════════════════════════════════════════
       3. VISITOR ID CARD MANAGEMENT
       ═════════════════════════════════════════════════════════════════════════ */

    /**
     * Dedicated Visitor ID Card Management submenu page.
     */
    public function visitorIndex(Request $request)
    {
        $this->idCardService->ensureDefaultTemplates();

        $tab = $request->get('tab', 'active'); // active, today, all, overstayed
        $search = $request->get('search');
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';
        $school = SchoolSetting::first();

        $query = Visitor::with(['loggedBy', 'hostEmployee', 'student']);

        if ($search) {
            $query->where(function ($sq) use ($search) {
                $sq->where('visitor_name', 'ilike', "%{$search}%")
                   ->orWhere('visitor_phone', 'ilike', "%{$search}%")
                   ->orWhere('pass_number', 'ilike', "%{$search}%")
                   ->orWhere('company_name', 'ilike', "%{$search}%")
                   ->orWhere('whom_to_meet', 'ilike', "%{$search}%")
                   ->orWhere('purpose', 'ilike', "%{$search}%");
            });
        }

        match ($tab) {
            'active' => $query->whereNull('out_time')->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED]),
            'today' => $query->whereDate('visit_date', today()),
            'overstayed' => $query->whereNull('out_time')
                                  ->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED])
                                  ->whereNotNull('expected_exit_time')
                                  ->where('expected_exit_time', '<', now()),
            default => null, // 'all'
        };

        $visitors = $query->latest('id')->paginate(20)->withQueryString();

        $activeInsideCount = Visitor::whereNull('out_time')->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED])->count();
        $todayTotal = Visitor::whereDate('visit_date', today())->count();
        $overstayCount = Visitor::whereNull('out_time')
            ->whereIn('status', [Visitor::STATUS_CHECKED_IN, Visitor::STATUS_APPROVED])
            ->whereNotNull('expected_exit_time')
            ->where('expected_exit_time', '<', now())
            ->count();

        $activeTemplate = IdCardTemplate::where('card_type', IdCard::TYPE_VISITOR)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first() ?? IdCardTemplate::where('card_type', IdCard::TYPE_VISITOR)->first();

        return view('gate.visitor-id-cards-index', compact(
            'visitors', 'activeInsideCount', 'todayTotal', 'overstayCount',
            'tab', 'search', 'orientation', 'school', 'activeTemplate'
        ));
    }

    /**
     * Preview single visitor ID card / badge.
     */
    public function singleVisitor(Request $request, int $id)
    {
        $visitor = Visitor::with(['loggedBy', 'hostEmployee', 'student'])->findOrFail($id);
        $school = SchoolSetting::first();
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';

        $card = $this->idCardService->getOrCreateCardForVisitor($visitor, null, $orientation);

        $template = $card->template ?? IdCardTemplate::where('card_type', IdCard::TYPE_VISITOR)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('visitor', $orientation);

        $barcodeVal = $card->barcode_value ?: ('VIS-' . ($visitor->pass_number ?: $visitor->id));
        $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 30, 2, '#0f172a');

        $auditLogs = $card->auditLogs()->with('performer')->get();
        $verifyUrl = $card->getQrVerificationUrl();

        return view('gate.single-visitor-id-card-view', compact(
            'visitor', 'card', 'school', 'orientation', 'template',
            'config', 'barcodeVal', 'barcodeSvg', 'auditLogs', 'verifyUrl'
        ));
    }

    /**
     * Download printable CR80 Visitor Pass PDF.
     */
    public function downloadVisitorPdf(Request $request, int $id)
    {
        $visitor = Visitor::with(['loggedBy', 'hostEmployee', 'student'])->findOrFail($id);
        $school = SchoolSetting::first();
        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';

        $card = $this->idCardService->getOrCreateCardForVisitor($visitor, null, $orientation);
        $barcodeVal = $card->barcode_value ?: ('VIS-' . ($visitor->pass_number ?: $visitor->id));
        $barcodeSvg = $this->idCardService->generateBarcodeSvg($barcodeVal, 26, 2, '#0f172a');

        $template = $card->template ?? IdCardTemplate::where('card_type', IdCard::TYPE_VISITOR)
            ->where('orientation', $orientation)
            ->where('is_active', true)
            ->first();

        $config = $template?->configuration_json ?? IdCardTemplate::getDefaultConfig('visitor', $orientation);

        $pdf = Pdf::loadView('pdf.visitor-id-card', compact('visitor', 'card', 'barcodeVal', 'barcodeSvg', 'school', 'orientation', 'config'));
        
        // Single CR80 card or A4 with cutting guide
        $format = $request->get('format', 'cr80'); // cr80 or a4
        if ($format === 'cr80') {
            if ($orientation === 'landscape') {
                $pdf->setPaper([0, 0, 242.65, 153.01], 'landscape'); // 85.6mm x 53.98mm in pt (approx 242.65 x 153.01 pt)
            } else {
                $pdf->setPaper([0, 0, 153.01, 242.65], 'portrait');
            }
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        $cleanPass = preg_replace('/[^A-Za-z0-9]/', '', $visitor->pass_number ?: ('VP' . $visitor->id));
        return $pdf->download("visitor-pass-{$cleanPass}-{$orientation}.pdf");
    }

    /* ═════════════════════════════════════════════════════════════════════════
       4. CENTRAL ID CARD SETTINGS & TEMPLATES
       ═════════════════════════════════════════════════════════════════════════ */

    /**
     * Central ID Card Settings and Template Management studio.
     */
    public function templates(Request $request)
    {
        $this->idCardService->ensureDefaultTemplates();

        $cardType = in_array($request->get('card_type'), [IdCard::TYPE_STUDENT, IdCard::TYPE_STAFF, IdCard::TYPE_VISITOR]) 
            ? $request->get('card_type') 
            : IdCard::TYPE_STUDENT;

        $orientation = in_array($request->get('orientation'), ['portrait', 'landscape']) ? $request->get('orientation') : 'landscape';

        $templates = IdCardTemplate::orderBy('card_type')->orderBy('orientation')->get();
        $selectedTemplate = IdCardTemplate::where('card_type', $cardType)
            ->where('orientation', $orientation)
            ->first() ?? $templates->first();

        $school = SchoolSetting::first();
        $sampleConfig = $selectedTemplate?->configuration_json ?? IdCardTemplate::getDefaultConfig($cardType, $orientation);

        return view('id-cards.settings', compact(
            'templates', 'selectedTemplate', 'cardType', 'orientation', 'school', 'sampleConfig'
        ));
    }

    /**
     * Save/update ID card template configuration.
     */
    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'template_id'         => 'required|exists:id_card_templates,id',
            'template_name'       => 'required|string|max:150',
            'primary_color'       => 'required|string|max:10',
            'secondary_color'     => 'required|string|max:10',
            'accent_gold'         => 'nullable|string|max:10',
            'bg_color'            => 'required|string|max:10',
            'text_color'          => 'required|string|max:10',
            'school_name'         => 'required|string|max:200',
            'affiliation_text'    => 'nullable|string|max:200',
            'motto'               => 'nullable|string|max:150',
            'school_address'      => 'nullable|string|max:255',
            'school_phone'        => 'nullable|string|max:50',
            'terms_text'          => 'nullable|string',
            'show_blood_group'    => 'nullable|boolean',
            'show_dob'            => 'nullable|boolean',
            'show_address'        => 'nullable|boolean',
            'show_parent_phone'   => 'nullable|boolean',
            'show_emergency_phone'=> 'nullable|boolean',
            'show_signature'      => 'nullable|boolean',
            'show_qr'             => 'nullable|boolean',
            'show_barcode'        => 'nullable|boolean',
        ]);

        $template = IdCardTemplate::findOrFail($data['template_id']);

        $config = $template->configuration_json ?? [];
        $config['primary_color']        = $data['primary_color'];
        $config['secondary_color']      = $data['secondary_color'];
        $config['accent_gold']          = $data['accent_gold'] ?? '#C59B27';
        $config['bg_color']             = $data['bg_color'];
        $config['text_color']           = $data['text_color'];
        $config['school_name']          = $data['school_name'];
        $config['affiliation_text']     = $data['affiliation_text'];
        $config['motto']                = $data['motto'];
        $config['school_address']       = $data['school_address'];
        $config['school_phone']         = $data['school_phone'];
        $config['terms_text']           = $data['terms_text'];
        $config['show_blood_group']     = $request->boolean('show_blood_group');
        $config['show_dob']             = $request->boolean('show_dob');
        $config['show_address']         = $request->boolean('show_address');
        $config['show_parent_phone']    = $request->boolean('show_parent_phone');
        $config['show_emergency_phone'] = $request->boolean('show_emergency_phone');
        $config['show_signature']       = $request->boolean('show_signature');
        $config['show_qr']              = $request->boolean('show_qr');
        $config['show_barcode']         = $request->boolean('show_barcode');

        $template->template_name = $data['template_name'];
        $template->configuration_json = $config;
        $template->version = $template->version + 1;
        $template->save();

        // Also update school settings for consistent global fallback
        $school = SchoolSetting::first();
        if ($school) {
            $school->primary_color = $data['primary_color'];
            $school->school_name = $data['school_name'];
            $school->affiliation_no = $data['affiliation_text'] ? '1931585' : $school->affiliation_no;
            $school->phone = $data['school_phone'] ?: $school->phone;
            $school->address = $data['school_address'] ?: $school->address;
            $school->id_card_header_color = $data['primary_color'];
            $school->id_card_text_color = $data['primary_color'];
            $school->id_card_bg_color = $data['bg_color'];
            $school->id_card_show_blood_group = $request->boolean('show_blood_group');
            $school->id_card_show_dob = $request->boolean('show_dob');
            $school->id_card_show_mobile = $request->boolean('show_parent_phone');
            $school->id_card_show_address = $request->boolean('show_address');
            $school->saveQuietly();
        }

        return redirect()->route('id-cards.settings', [
            'card_type'   => $template->card_type,
            'orientation' => $template->orientation,
        ])->with('success', "Template '{$template->template_name}' (v{$template->version}) updated successfully.");
    }

    /* ═════════════════════════════════════════════════════════════════════════
       5. LIFECYCLE MANAGEMENT & REPRINT / REPLACE / REVOKE
       ═════════════════════════════════════════════════════════════════════════ */

    public function reprint(Request $request, int $cardId)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $card = IdCard::findOrFail($cardId);

        $this->idCardService->reprintCard($card, $request->get('reason'));

        return back()->with('success', "Card #{$card->card_number} reprint logged (Reprint #{$card->reprint_count}).");
    }

    public function replace(Request $request, int $cardId)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $card = IdCard::findOrFail($cardId);

        $newCard = $this->idCardService->replaceCard($card, $request->get('reason'));

        return back()->with('success', "Card #{$card->card_number} replaced with new active Card #{$newCard->card_number}. Previous card marked as replaced.");
    }

    public function revoke(Request $request, int $cardId)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $card = IdCard::findOrFail($cardId);

        $this->idCardService->revokeCard($card, $request->get('reason'));

        return back()->with('success', "Card #{$card->card_number} revoked successfully.");
    }

    /* ═════════════════════════════════════════════════════════════════════════
       6. AUDIT LOGS & REPORTING
       ═════════════════════════════════════════════════════════════════════════ */

    public function auditLogs(Request $request)
    {
        $search = $request->get('search');
        $action = $request->get('action');
        $cardType = $request->get('card_type');

        $query = IdCardAuditLog::with(['card', 'performer']);

        if ($action) {
            $query->where('action', $action);
        }

        if ($cardType) {
            $query->whereHas('card', fn($cq) => $cq->where('card_type', $cardType));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'ilike', "%{$search}%")
                  ->orWhereHas('card', fn($cq) => $cq->where('card_number', 'ilike', "%{$search}%"));
            });
        }

        $logs = $query->latest('timestamp')->paginate(30)->withQueryString();

        return view('id-cards.audit-logs', compact('logs', 'search', 'action', 'cardType'));
    }

    /* ═════════════════════════════════════════════════════════════════════════
       7. SECURE PUBLIC QR VERIFICATION
       ═════════════════════════════════════════════════════════════════════════ */

    /**
     * Controlled verification endpoint for QR code scans.
     * Adheres to strict privacy rules: minimum approved details displayed.
     */
    public function verify(string $token)
    {
        $card = IdCard::where('qr_verification_token', $token)->first();
        $school = SchoolSetting::first();

        if (!$card) {
            return view('id-cards.verify-result', [
                'isValid'    => false,
                'card'       => null,
                'school'     => $school,
                'statusText' => 'CARD NOT FOUND / INVALID TOKEN',
                'reason'     => 'The scanned ID Card verification token does not match any record in the institutional database. This card may be fraudulent or unauthorized.',
                'badgeClass' => 'bg-red-100 text-red-800 border-red-300',
            ]);
        }

        $isValid = $card->isCardValid();
        $statusText = match ($card->status) {
            IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED => $card->expiry_date && $card->expiry_date->isPast() ? 'EXPIRED' : 'VERIFIED & ACTIVE',
            IdCard::STATUS_EXPIRED => 'EXPIRED CARD',
            IdCard::STATUS_REPLACED => 'REPLACED / DEACTIVATED (DO NOT HONOR)',
            IdCard::STATUS_REVOKED => 'REVOKED (DO NOT HONOR)',
            IdCard::STATUS_LOST => 'FLAGGED AS LOST',
            IdCard::STATUS_DAMAGED => 'FLAGGED AS DAMAGED',
            default => strtoupper($card->status),
        };

        $badgeClass = match ($card->status) {
            IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED => $card->expiry_date && $card->expiry_date->isPast() ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300',
            IdCard::STATUS_REPLACED, IdCard::STATUS_REVOKED, IdCard::STATUS_LOST => 'bg-red-100 text-red-800 border-red-300',
            default => 'bg-slate-100 text-slate-800 border-slate-300',
        };

        // Snapshot details (safe, non-sensitive)
        $snapshot = $card->field_snapshot_json ?? [];

        return view('id-cards.verify-result', [
            'isValid'    => $isValid,
            'card'       => $card,
            'school'     => $school,
            'statusText' => $statusText,
            'badgeClass' => $badgeClass,
            'snapshot'   => $snapshot,
        ]);
    }
}
