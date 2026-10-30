<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Models\EnquiryStatusHistory;
use App\Models\AdmissionDraft;
use App\Models\AdmissionDraftDocument;
use App\Models\Student;
use App\Models\AdmissionStage;
use App\Models\SeatCapacity;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Exports\EnquiriesExport;
use App\Exports\ArrayExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdmissionController extends Controller
{
    /**
     * Whether the 2-tier approval workflow (Principal -> Admin confirmation) is active.
     * Temporarily disabled per user request. Toggle this to true whenever requested to re-enable it.
     */
    protected bool $requireApprovalWorkflow = false;

    public function getFeeStructureData(): array
    {
        $academicYear = AcademicYear::current();
        $yearId = (int) ($academicYear?->id ?? 0);

        return Cache::remember("admission_fee_structure_data_{$yearId}", 3600, function () use ($academicYear) {
            $classes = Classes::with('sections')
                ->active()
                ->orderBy('sort_order')
                ->orderBy('numeric_value')
                ->get()
                ->unique('name')
                ->values();

            $enquiryCounts = Enquiry::selectRaw('class_id, count(*) as total')
                ->groupBy('class_id')
                ->pluck('total', 'class_id');

        // Official Fee Structure 2026-2027 matching school schedule
        $officialFees = [
            'pre_kg' => [
                'name'          => 'PRE KG',
                'material_fee'  => 0,
                'term1_fee'     => 21000,
                'term2_fee'     => 10000,
                'term3_fee'     => 10000,
                'total_basic'   => 41000,
                'admission_fee' => 2500,
                'tier'          => 'Pre-KG',
            ],
            'junior_kg' => [
                'name'          => 'JUNIOR KG',
                'material_fee'  => 0,
                'term1_fee'     => 22000,
                'term2_fee'     => 10000,
                'term3_fee'     => 10000,
                'total_basic'   => 42000,
                'admission_fee' => 2500,
                'tier'          => 'Junior KG (LKG)',
            ],
            'senior_kg' => [
                'name'          => 'SENIOR KG',
                'material_fee'  => 0,
                'term1_fee'     => 24500,
                'term2_fee'     => 10000,
                'term3_fee'     => 10000,
                'total_basic'   => 44500,
                'admission_fee' => 2500,
                'tier'          => 'Senior KG (UKG)',
            ],
            '1' => [
                'name'          => 'GRADE I',
                'material_fee'  => 0,
                'term1_fee'     => 25000,
                'term2_fee'     => 11000,
                'term3_fee'     => 11000,
                'total_basic'   => 47000,
                'admission_fee' => 2500,
                'tier'          => 'Grade I',
            ],
            '2' => [
                'name'          => 'GRADE II',
                'material_fee'  => 0,
                'term1_fee'     => 25000,
                'term2_fee'     => 12250,
                'term3_fee'     => 12250,
                'total_basic'   => 49500,
                'admission_fee' => 2500,
                'tier'          => 'Grade II',
            ],
            '3' => [
                'name'          => 'GRADE III',
                'material_fee'  => 0,
                'term1_fee'     => 26000,
                'term2_fee'     => 13000,
                'term3_fee'     => 13000,
                'total_basic'   => 52000,
                'admission_fee' => 2500,
                'tier'          => 'Grade III',
            ],
            '4' => [
                'name'          => 'GRADE IV',
                'material_fee'  => 0,
                'term1_fee'     => 27000,
                'term2_fee'     => 14000,
                'term3_fee'     => 14000,
                'total_basic'   => 55000,
                'admission_fee' => 2500,
                'tier'          => 'Grade IV',
            ],
            '5' => [
                'name'          => 'GRADE V',
                'material_fee'  => 0,
                'term1_fee'     => 28500,
                'term2_fee'     => 15000,
                'term3_fee'     => 15000,
                'total_basic'   => 58500,
                'admission_fee' => 2500,
                'tier'          => 'Grade V',
            ],
            '6' => [
                'name'          => 'GRADE VI',
                'material_fee'  => 0,
                'term1_fee'     => 30000,
                'term2_fee'     => 15500,
                'term3_fee'     => 15500,
                'total_basic'   => 61000,
                'admission_fee' => 2500,
                'tier'          => 'Grade VI',
            ],
            '7' => [
                'name'          => 'GRADE VII',
                'material_fee'  => 0,
                'term1_fee'     => 31000,
                'term2_fee'     => 16000,
                'term3_fee'     => 16000,
                'total_basic'   => 63000,
                'admission_fee' => 2500,
                'tier'          => 'Grade VII',
            ],
            '8' => [
                'name'          => 'GRADE VIII',
                'material_fee'  => 0,
                'term1_fee'     => 32000,
                'term2_fee'     => 16750,
                'term3_fee'     => 16750,
                'total_basic'   => 65500,
                'admission_fee' => 2500,
                'tier'          => 'Grade VIII',
            ],
            '9' => [
                'name'          => 'GRADE IX',
                'material_fee'  => 0,
                'term1_fee'     => 34000,
                'term2_fee'     => 17500,
                'term3_fee'     => 17500,
                'total_basic'   => 69000,
                'admission_fee' => 2500,
                'tier'          => 'Grade IX',
            ],
            '10' => [
                'name'          => 'GRADE X',
                'material_fee'  => 19500,
                'term1_fee'     => 18000,
                'term2_fee'     => 18000,
                'term3_fee'     => 18000,
                'total_basic'   => 73500,
                'admission_fee' => 0,
                'tier'          => 'Grade X',
            ],
            '11' => [
                'name'          => 'GRADE XI',
                'material_fee'  => 0,
                'term1_fee'     => 40000,
                'term2_fee'     => 20000,
                'term3_fee'     => 20000,
                'total_basic'   => 80000,
                'admission_fee' => 2500,
                'tier'          => 'Grade XI',
                'integrated'    => [
                    'term1_fee'   => 52500,
                    'term2_fee'   => 32500,
                    'term3_fee'   => 20000,
                    'total_basic' => 105000,
                ],
            ],
            '12' => [
                'name'          => 'GRADE XII',
                'material_fee'  => 0,
                'term1_fee'     => 40000,
                'term2_fee'     => 20000,
                'term3_fee'     => 20000,
                'total_basic'   => 80000,
                'admission_fee' => 2500,
                'tier'          => 'Grade XII',
                'integrated'    => [
                    'term1_fee'   => 52500,
                    'term2_fee'   => 32500,
                    'term3_fee'   => 20000,
                    'total_basic' => 105000,
                ],
            ],
        ];

        $standardFees = [];
        foreach ($classes as $cls) {
            $num = (int)($cls->numeric_value ?? 0);
            $cleanName = strtolower(trim($cls->name));

            $key = null;
            if (str_contains($cleanName, 'pre')) {
                $key = 'pre_kg';
            } elseif (str_contains($cleanName, 'lkg') || str_contains($cleanName, 'junior')) {
                $key = 'junior_kg';
            } elseif (str_contains($cleanName, 'ukg') || str_contains($cleanName, 'senior')) {
                $key = 'senior_kg';
            } elseif ($num >= 1 && $num <= 12) {
                $key = (string)$num;
            } elseif ($cleanName === 'i') {
                $key = '1';
            } elseif ($cleanName === 'ii') {
                $key = '2';
            } elseif ($cleanName === 'iii') {
                $key = '3';
            } elseif ($cleanName === 'iv') {
                $key = '4';
            } elseif ($cleanName === 'v') {
                $key = '5';
            } elseif ($cleanName === 'vi') {
                $key = '6';
            } elseif ($cleanName === 'vii') {
                $key = '7';
            } elseif ($cleanName === 'viii') {
                $key = '8';
            } elseif ($cleanName === 'ix') {
                $key = '9';
            } elseif ($cleanName === 'x') {
                $key = '10';
            } elseif ($cleanName === 'xi') {
                $key = '11';
            } elseif ($cleanName === 'xii') {
                $key = '12';
            }

            $fee = $officialFees[$key] ?? [
                'name'          => 'CLASS ' . $cls->name,
                'material_fee'  => 0,
                'term1_fee'     => 25000,
                'term2_fee'     => 11000,
                'term3_fee'     => 11000,
                'total_basic'   => 47000,
                'admission_fee' => 2500,
                'tier'          => 'Class ' . $cls->name,
            ];

            $materialFee  = (float)($fee['material_fee'] ?? 0);
            $term1Fee     = (float)($fee['term1_fee'] ?? 0);
            $term2Fee     = (float)($fee['term2_fee'] ?? 0);
            $term3Fee     = (float)($fee['term3_fee'] ?? 0);
            $totalBasic   = (float)($fee['total_basic'] ?? ($term1Fee + $term2Fee + $term3Fee + $materialFee));
            $admissionFee = (float)($fee['admission_fee'] ?? 2500);
            $tier         = $fee['tier'] ?? ('Class ' . $cls->name);

            // Backward compatibility components:
            $tuition = $term1Fee;
            $book    = $materialFee > 0 ? $materialFee : $admissionFee;
            $exam    = $term2Fee;
            $lab     = $term3Fee;
            $hostel  = 0;
            $combined = $totalBasic;

            $standardFees[$cls->id] = [
                'class_id'             => $cls->id,
                'class_name'           => $cls->name,
                'official_name'        => $fee['name'] ?? ('GRADE ' . $cls->name),
                'tier'                 => $tier,
                'material_fee'         => $materialFee,
                'term1_fee'            => $term1Fee,
                'term2_fee'            => $term2Fee,
                'term3_fee'            => $term3Fee,
                'admission_fee'        => $admissionFee,
                'total_basic'          => $totalBasic,
                'total_annual'         => $totalBasic, // For backwards compatibility
                'total_with_admission' => $totalBasic + $admissionFee,
                'term_fee'             => round($totalBasic / 3),
                'term1_date'           => '01.04.2026',
                'term2_date'           => '05.08.2026',
                'term3_date'           => '05.12.2026',
                'term1_iso'            => '2026-04-01',
                'term2_iso'            => '2026-08-05',
                'term3_iso'            => '2026-12-05',
                'tuition_fee'          => $tuition,
                'book_fee'             => $book,
                'exam_fee'             => $exam,
                'lab_fee'              => $lab,
                'hostel_annual'        => 0,
                'hostel_monthly'       => 0,
                'combined_total'       => $totalBasic,
                'has_integrated'       => isset($fee['integrated']),
                'integrated_fee'       => $fee['integrated']['total_basic'] ?? null,
                'integrated_term1'     => $fee['integrated']['term1_fee'] ?? null,
                'integrated_term2'     => $fee['integrated']['term2_fee'] ?? null,
                'integrated_term3'     => $fee['integrated']['term3_fee'] ?? null,
                'enquiries_count'      => $enquiryCounts[$cls->id] ?? 0,
            ];
        }

        $activities = [
            ['id' => 'western_dance',  'name' => 'Western Dance',   'annual_fee' => 3500, 'label' => '₹3,500/yr'],
            ['id' => 'classical_dance','name' => 'Classical Dance', 'annual_fee' => 4000, 'label' => '₹4,000/yr'],
            ['id' => 'yoga',           'name' => 'Yoga',            'annual_fee' => 2500, 'label' => '₹2,500/yr'],
            ['id' => 'skating',        'name' => 'Skating',         'annual_fee' => 4500, 'label' => '₹4,500/yr'],
            ['id' => 'band',           'name' => 'Band',            'annual_fee' => 5000, 'label' => '₹5,000/yr'],
            ['id' => 'keyboard',       'name' => 'Keyboard Class',  'annual_fee' => 4200, 'label' => '₹4,200/yr'],
            ['id' => 'kungfu',         'name' => 'Kungfu',          'annual_fee' => 3800, 'label' => '₹3,800/yr'],
            ['id' => 'swimming',       'name' => 'Swimming',        'annual_fee' => 6000, 'label' => '₹6,000/yr'],
        ];

            return compact('classes', 'academicYear', 'standardFees', 'activities');
        });
    }

    public function feeStructure(Request $request)
    {
        $data = $this->getFeeStructureData();
        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admissions.fee-structure', array_merge($data, compact('users')));
    }

    public function index(Request $request)
    {
        $query = Enquiry::with(['class', 'academicYear'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('student_name', 'like', "%$v%")
                  ->orWhere('parent_mobile', 'like', "%$v%")
                  ->orWhere('enquiry_number', 'like', "%$v%");
            }))
            ->latest();

        $enquiries = $query->paginate(20)->withQueryString();
        $feeData   = $this->getFeeStructureData();
        $classes   = $feeData['classes'];

        $stats = Cache::remember('admission_index_stats_v2', 30, function () {
            $statsRow = DB::table('enquiries')->whereNull('deleted_at')->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new,
                COUNT(CASE WHEN status = 'follow_up' THEN 1 END) as follow_up,
                COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted,
                COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost
            ")->first();

            $total = (int) ($statsRow->total ?? 0);
            $converted = (int) ($statsRow->converted ?? 0);
            $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;
            $draftsCount = \App\Models\AdmissionDraft::whereIn('status', ['draft', 'submission_failed'])->count();

            return [
                'total'           => $total,
                'new'             => (int) ($statsRow->new ?? 0),
                'follow_up'       => (int) ($statsRow->follow_up ?? 0),
                'converted'       => $converted,
                'lost'            => (int) ($statsRow->lost ?? 0),
                'conversion_rate' => $rate,
                'drafts_count'    => $draftsCount,
            ];
        });

        return view('admissions.index', array_merge($feeData, compact('enquiries', 'classes', 'stats')));
    }

    public function create(Request $request)
    {
        $feeData      = $this->getFeeStructureData();
        $classes      = $feeData['classes'];
        $sections     = \App\Models\Section::where('is_active', true)->get(['id', 'class_id', 'name']);
        $academicYear = $feeData['academicYear'];
        $standardFees = $feeData['standardFees'];
        $activities   = $feeData['activities'];
        $users        = \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Handle Enquiry Conversion pre-fill
        $enquiry = null;
        if ($request->filled('enquiry_id')) {
            $enquiry = Enquiry::with(['class', 'academicYear'])->find($request->enquiry_id);
            if ($enquiry && ($enquiry->status === 'converted' || $enquiry->converted_student_id)) {
                $convertedStudent = $enquiry->convertedStudent ?? \App\Models\Student::find($enquiry->converted_student_id);
                if ($convertedStudent) {
                    return redirect()->route('students.show', $convertedStudent->id)
                        ->with('info', "Enquiry #{$enquiry->enquiry_number} has already been converted into an admission for student {$convertedStudent->first_name} {$convertedStudent->last_name} ({$convertedStudent->admission_no}).");
                }
            }
        }

        // Handle Draft resumption pre-fill
        $draft = null;
        if ($request->filled('draft_id')) {
            $draft = AdmissionDraft::with('documents')->find($request->draft_id);
        }

        // Fetch standard-wise admission kits and inventory stock data
        $admissionKits = \App\Models\AdmissionKitConfig::with('item')
            ->get()
            ->groupBy('class_id');

        // Fetch active transport routes with vehicle and active stops for Transport facility
        $transportRoutes = \App\Models\TransportRoute::with([
            'vehicle',
            'stops' => fn($q) => $q->where('is_active', true)->orderBy('stop_order')
        ])
            ->where('is_active', true)
            ->get();

        // Concession categories specified by school
        $concessionTypes = [
            'none'        => 'No Concession',
            'staff_kid'   => 'Staff Kid (50% Concession)',
            'topper'      => 'Academic Topper Concession',
            'sports'      => 'Sports / Athletic Quota',
            'single_shot' => 'Single Shot Payment (Full Payment Discount)',
            'referral'    => 'Parent / Staff Referral Concession',
            'sibling'     => 'Sibling Concession',
        ];

        // Mandatory & Optional official documents submitted at admission
        $officialDocChecklist = [
            'birth_certificate'     => 'Birth Certificate',
            'aadhaar_card'          => 'Aadhaar Card (Student & Parents)',
            'community_certificate' => 'Community Certificate',
            'transfer_certificate'  => 'Transfer Certificate (TC - Original)',
            'marksheet'             => 'Previous Marksheet / Report Card',
            'migration_certificate' => 'Migration Certificate',
            'emis_slip'             => 'Previous EMIS / PEN Number Slip',
        ];

        // Fetch academic years and groups for Books & Notes auto-checklist
        $academicYears = \App\Models\AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $academicGroups = \App\Models\AcademicGroup::where('is_active', true)->orderBy('display_order')->get();

        return view('admissions.create', compact(
            'classes', 'sections', 'academicYear', 'academicYears', 'academicGroups', 'standardFees', 'activities', 'users', 'admissionKits',
            'transportRoutes', 'concessionTypes', 'officialDocChecklist', 'enquiry', 'draft'
        ));
    }

    /**
     * AJAX Endpoint to Auto-Fetch student by Roll Number (or Admission Number)
     */
    public function lookupSibling(Request $request)
    {
        $roll = trim($request->get('roll_number', ''));
        if (!$roll) {
            return response()->json(['found' => false, 'message' => 'Please enter a Roll No.']);
        }

        // Search by roll_number or admission_no in students table
        $student = \App\Models\Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->where(function ($q) use ($roll) {
                $q->where('roll_number', $roll)
                  ->orWhereRaw('LOWER(roll_number) = ?', [strtolower($roll)])
                  ->orWhere('admission_no', $roll)
                  ->orWhereRaw('LOWER(admission_no) = ?', [strtolower($roll)]);
            })
            ->first();

        // If not found, check student_enrollments table by roll_number
        if (!$student) {
            $enrollment = \App\Models\StudentEnrollment::with(['student', 'class', 'section'])
                ->where(function ($q) use ($roll) {
                    $q->where('roll_number', $roll)
                      ->orWhereRaw('LOWER(roll_number) = ?', [strtolower($roll)]);
                })
                ->latest('id')
                ->first();

            if ($enrollment && $enrollment->student) {
                $student = $enrollment->student;
                $student->setRelation('currentEnrollment', $enrollment);
            }
        }

        if (!$student) {
            return response()->json([
                'found'   => false,
                'message' => "No student found with Roll No: {$roll}",
            ]);
        }

        $fullName = trim($student->first_name . ' ' . ($student->last_name ?? ''));
        $className = $student->currentEnrollment?->class?->name ?? '';
        $sectionName = $student->currentEnrollment?->section?->name ?? '';
        $classSection = trim(($className ? 'Class ' . $className : '') . ($sectionName ? ' - Section ' . $sectionName : ''));

        return response()->json([
            'found'   => true,
            'student' => [
                'id'            => $student->id,
                'name'          => $fullName,
                'roll_number'   => $student->roll_number ?: $roll,
                'admission_no'  => $student->admission_no,
                'class_section' => $classSection ?: ('Class ' . ($student->class_id ?? 'N/A')),
            ],
        ]);
    }

    /**
     * AJAX Endpoint to check real-time uniqueness of Government Document numbers and student data
     * (Aadhaar number, EMIS / PEN number, student email)
     * NOTE: Mobile numbers are explicitly excluded to support siblings sharing parent contacts.
     */
    public function checkUnique(Request $request)
    {
        $field = trim((string)$request->get('field', ''));
        $value = trim((string)$request->get('value', ''));
        $excludeId = $request->filled('exclude_id') ? (int)$request->get('exclude_id') : null;

        if (!$field || $value === '') {
            return response()->json([
                'available' => true,
                'field'     => $field,
                'message'   => 'Value is empty',
            ]);
        }

        // 1. Student Aadhaar Number Check
        if ($field === 'aadhaar_no') {
            $digits = preg_replace('/[^0-9]/', '', $value);
            if (strlen($digits) < 12) {
                return response()->json([
                    'available'  => true,
                    'incomplete' => true,
                    'field'      => 'aadhaar_no',
                    'message'    => 'Entering 12 digits...',
                ]);
            }

            $existing = \App\Models\Student::findStudentByAadhaar($digits, $excludeId);
            if ($existing) {
                return response()->json([
                    'available' => false,
                    'field'     => 'aadhaar_no',
                    'message'   => "Already used for student: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']})",
                    'student'   => $existing,
                ]);
            }

            return response()->json([
                'available' => true,
                'field'     => 'aadhaar_no',
                'message'   => 'Valid & Available',
            ]);
        }

        // 2. EMIS / PEN Number Check
        if ($field === 'emis_no') {
            $existing = \App\Models\Student::findStudentByEmis($value, $excludeId);
            if ($existing) {
                return response()->json([
                    'available' => false,
                    'field'     => 'emis_no',
                    'message'   => "Already used for student: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']})",
                    'student'   => $existing,
                ]);
            }

            return response()->json([
                'available' => true,
                'field'     => 'emis_no',
                'message'   => 'Available',
            ]);
        }

        // 3. Student Email Address Check (Class 9+)
        if ($field === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'available' => true,
                    'field'     => 'email',
                    'message'   => 'Invalid email format',
                ]);
            }

            $existing = \App\Models\Student::findStudentByEmail($value, $excludeId);
            if ($existing) {
                return response()->json([
                    'available' => false,
                    'field'     => 'email',
                    'message'   => "Already registered to student: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']})",
                    'student'   => $existing,
                ]);
            }

            return response()->json([
                'available' => true,
                'field'     => 'email',
                'message'   => 'Available',
            ]);
        }

        return response()->json([
            'available' => true,
            'field'     => $field,
            'message'   => 'Field check passed',
        ]);
    }

    public function printFeeStructure($classId = null)
    {
        $feeData      = $this->getFeeStructureData();
        $classes      = $feeData['classes'];
        $academicYear = $feeData['academicYear'];
        $standardFees = $feeData['standardFees'];
        $activities   = $feeData['activities'];
        $school       = \App\Models\SchoolSetting::first() ?? (object)[
            'school_name' => 'ERODE PUBLIC SCHOOL',
            'tagline'     => 'Affiliated to CBSE, New Delhi (Affiliation No: 1930965)',
            'phone'       => '+91 98427 88888',
            'email'       => 'info@erodepublicschool.edu.in',
            'website'     => 'www.erodepublicschool.edu.in',
            'address'     => 'Chennimalai Road, Erode, Tamil Nadu'
        ];

        $selectedClass = $classId ? $classes->firstWhere('id', $classId) : $classes->first();
        if (!$selectedClass && $classes->count() > 0) {
            $selectedClass = $classes->first();
        }

        $currentFee = $selectedClass ? ($standardFees[$selectedClass->id] ?? null) : null;
        if (!$currentFee) {
            $currentFee = [
                'term1_fee'     => 21000,
                'term2_fee'     => 10000,
                'term3_fee'     => 10000,
                'material_fee'  => 0,
                'admission_fee' => 2500,
                'total_basic'   => 41000,
                'tier'          => 'Pre-KG',
                'term1_date'    => '01.04.2026',
                'term2_date'    => '05.08.2026',
                'term3_date'    => '05.12.2026',
            ];
        }

        return view('admissions.print-fee-structure', compact(
            'classes', 'academicYear', 'standardFees', 'activities', 'school', 'selectedClass', 'currentFee'
        ));
    }

    public function printForm(Request $request)
    {
        $formType = $request->get('form', 'admission'); // 'admission', 'grade11', 'enquiry'
        $student  = $request->filled('student_id') ? \App\Models\Student::find($request->student_id) : null;
        $enquiry  = $request->filled('enquiry_id') ? \App\Models\Enquiry::find($request->enquiry_id) : null;

        $feeData      = $this->getFeeStructureData();
        $classes      = $feeData['classes'];
        $sections     = \App\Models\Section::where('is_active', true)->get(['id', 'class_id', 'name']);
        $academicYear = $feeData['academicYear'];
        $activities   = $feeData['activities'];
        $school       = \App\Models\SchoolSetting::first();

        return view('admissions.print-application-form', compact(
            'formType', 'student', 'enquiry', 'classes', 'sections', 'academicYear', 'activities', 'school'
        ));
    }

    public function savePrintForm(Request $request)
    {
        $validated = $request->validate([
            'student_name'          => 'required|string|max:150',
            'form_type'             => 'nullable|string|in:admission,grade11,enquiry',
            'class_id'              => 'nullable',
            'dob'                   => 'nullable|date',
            'gender'                => 'nullable|string|max:10',
            'father_name'           => 'nullable|string|max:100',
            'mother_name'           => 'nullable|string|max:100',
            'parent_mobile'         => 'nullable|string|max:20',
            'father_mobile'         => 'nullable|string|max:20',
            'mother_mobile'         => 'nullable|string|max:20',
            'father_occupation'     => 'nullable|string|max:100',
            'mother_occupation'     => 'nullable|string|max:100',
            'father_qualification'  => 'nullable|string|max:100',
            'mother_qualification'  => 'nullable|string|max:100',
            'father_income'         => 'nullable|string|max:100',
            'mother_income'         => 'nullable|string|max:100',
            'address'               => 'nullable|string|max:500',
            'previous_school'       => 'nullable|string|max:150',
            'stream_group'          => 'nullable|string|max:50',
            'stream_group_allotted' => 'nullable|string|max:50',
            'aadhaar_no'            => 'nullable|string|max:25',
            'religion'              => 'nullable|string|max:50',
            'caste'                 => 'nullable|string|max:50',
            'blood_group'           => 'nullable|string|max:10',
        ]);

        $academicYear = \App\Models\AcademicYear::current();
        $mobile = $validated['father_mobile'] ?? ($validated['parent_mobile'] ?? ($validated['mother_mobile'] ?? '9999999999'));

        // Resolve class_id if numeric
        $classId = is_numeric($request->class_id) ? (int)$request->class_id : null;

        $enquiry = \App\Models\Enquiry::create([
            'enquiry_number'        => \App\Models\Enquiry::generateNumber(),
            'academic_year_id'      => $academicYear?->id,
            'student_name'          => $validated['student_name'],
            'class_id'              => $classId,
            'dob'                   => $validated['dob'] ?? null,
            'gender'                => $validated['gender'] ?? 'male',
            'parent_name'           => $validated['father_name'] ?? ($validated['mother_name'] ?? 'Parent'),
            'parent_mobile'         => $mobile,
            'father_name'           => $validated['father_name'] ?? null,
            'mother_name'           => $validated['mother_name'] ?? null,
            'father_mobile'         => $validated['father_mobile'] ?? null,
            'mother_mobile'         => $validated['mother_mobile'] ?? null,
            'father_occupation'     => $validated['father_occupation'] ?? null,
            'mother_occupation'     => $validated['mother_occupation'] ?? null,
            'father_qualification'  => $validated['father_qualification'] ?? null,
            'mother_qualification'  => $validated['mother_qualification'] ?? null,
            'father_income'         => $validated['father_income'] ?? null,
            'mother_income'         => $validated['mother_income'] ?? null,
            'address'               => $validated['address'] ?? null,
            'previous_school'       => $validated['previous_school'] ?? null,
            'stream_group'          => $validated['stream_group'] ?? null,
            'stream_group_allotted' => $validated['stream_group_allotted'] ?? null,
            'status'                => 'new',
            'source'                => 'walk_in',
        ]);

        return response()->json([
            'success'        => true,
            'message'        => 'Applicant record successfully saved to Admissions!',
            'enquiry_id'     => $enquiry->id,
            'enquiry_number' => $enquiry->enquiry_number,
        ]);
    }

    public function store(Request $request)
    {
        // Prevent duplicate admissions if user retries or double-clicks submit
        $submissionToken = $request->input('submission_token');
        if ($submissionToken) {
            $existingStudentId = Cache::get("admission_sub_{$submissionToken}");
            if ($existingStudentId) {
                $existingStudent = \App\Models\Student::find($existingStudentId);
                if ($existingStudent) {
                    return redirect()->route('admissions.submission-summary', $existingStudent->id)
                        ->with('info', 'Admission record was already submitted. Displaying admission summary.');
                }
            }
        }

        // Sanitize & normalize section_id if string like "section_a" or non-numeric
        if ($request->has('section_id') && $request->section_id && !is_numeric($request->section_id)) {
            $secStr = str_replace(['section_', 'Section ', 'sec_'], '', strtolower($request->section_id));
            $foundSec = \App\Models\Section::where('class_id', $request->class_id)
                ->where(function($q) use ($secStr) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($secStr)])
                      ->orWhereRaw('LOWER(name) = ?', ['section ' . strtolower($secStr)])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($secStr) . '%']);
                })->first();
            if ($foundSec) {
                $request->merge(['section_id' => $foundSec->id]);
            } else {
                $request->merge(['section_id' => null]);
            }
        }

        // Sanitize & normalize digits-only fields (mobile numbers, aadhaar, pincode)
        $digitsOnlyFields = ['parent_mobile', 'mother_mobile', 'guardian_mobile', 'aadhaar_no', 'father_aadhaar', 'pincode'];
        $sanitizedDigits = [];
        foreach ($digitsOnlyFields as $f) {
            if ($request->filled($f)) {
                $cleaned = preg_replace('/[^\d]/', '', (string)$request->input($f));
                // If user entered +91 or 91 prefix with 12 digits for phone numbers, normalize to 10 digits
                if (in_array($f, ['parent_mobile', 'mother_mobile', 'guardian_mobile']) && strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
                    $cleaned = substr($cleaned, 2);
                }
                $sanitizedDigits[$f] = $cleaned !== '' ? $cleaned : null;
            } elseif ($request->has($f)) {
                $sanitizedDigits[$f] = null;
            }
        }
        if (!empty($sanitizedDigits)) {
            $request->merge($sanitizedDigits);
        }

        if ($request->has('emis_no')) {
            $emis = trim((string)$request->input('emis_no'));
            $request->merge(['emis_no' => $emis !== '' ? $emis : null]);
        }

        $validated = $request->validate([
            'first_name'             => 'required|string|min:2|max:50',
            'last_name'              => 'nullable|string|max:50',
            'email'                  => 'nullable|email:rfc|max:100',
            'dob'                    => 'required|date|before_or_equal:today',
            'gender'                 => 'required|in:male,female,other',
            'photo'                  => 'nullable|image|max:3072',
            'class_id'               => 'required|exists:classes,id',
            'section_id'             => 'nullable|exists:sections,id',
            'parent_name'            => 'required|string|min:2|max:100',
            'parent_mobile'          => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'parent_email'           => 'nullable|email:rfc|max:100',
            'father_occupation'      => 'nullable|string|max:100',
            'mother_name'            => 'nullable|string|max:100',
            'mother_occupation'      => 'nullable|string|max:100',
            'mother_mobile'          => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'mother_email'           => 'nullable|email:rfc|max:100',
            'guardian_name'          => 'nullable|string|max:100',
            'guardian_mobile'        => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'guardian_relation'      => 'nullable|string|max:50',
            'annual_family_income'   => 'nullable|numeric|min:0',
            'address'                => 'nullable|string|max:255',
            'source'                 => 'nullable|string|max:50',
            'referred_by'            => 'nullable|string|max:100',
            'notes'                  => 'nullable|string',
            'follow_up_date'         => 'nullable|date',
            'previous_school'        => 'nullable|string|max:150',
            'previous_class'         => 'nullable|string|max:50',
            'previous_percentage'    => 'nullable|numeric|between:0,100',
            'assigned_to'            => 'nullable|exists:users,id',
            'emis_no'                => 'nullable|string|max:50',
            'identification_mark_1'  => 'nullable|string|max:255',
            'identification_mark_2'  => 'nullable|string|max:255',
            'is_asp'                 => 'nullable|boolean',
            'asp_fee'                => 'nullable|numeric|min:0',
            'transport_route_id'     => 'nullable|exists:transport_routes,id',
            'transport_stop_id'      => 'nullable|exists:transport_stops,id',
            'transport_distance_km'  => 'nullable|numeric|min:0',
            'transport_fee'          => 'nullable|numeric|min:0',
            'concession_type'        => 'nullable|string|max:50',
            'concession_amount'      => 'nullable|numeric|min:0',
            'concession_remarks'     => 'nullable|string|max:255',
            'sibling_name'           => 'nullable|string|max:255',
            'sibling_roll_no'        => 'nullable|string|max:50',
            'sibling_admission_no'   => 'nullable|string|max:50',
            'sibling_class'          => 'nullable|string|max:50',
            'documents_submitted'    => 'nullable|array',
            'activities'             => 'nullable|array',
            'payment_terms'          => 'required|in:single,2_terms,3_terms',
            'payment_mode'           => 'required|string|max:50',
            'payment_account'        => 'nullable|string|in:upi,cash_box_1,cash_box_2,bank',
            'transaction_id'         => 'nullable|string|max:100',
            'amount_collected'       => 'required|numeric|min:0',
            'payment_date'           => 'nullable|date',
            'term_2_due_date'        => 'nullable|date',
            'term_3_due_date'        => 'nullable|date',
            'blood_group'            => 'nullable|string|max:10',
            'category'               => 'nullable|string|max:50',
            'religion'               => 'nullable|string|max:50',
            'mother_tongue'          => 'nullable|string|max:50',
            'aadhaar_no'             => ['nullable', 'string', 'regex:/^[0-9]{12}$/'],
            'pincode'                => ['nullable', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'father_photo'           => 'nullable|image|max:3072',
            'father_aadhaar'         => ['nullable', 'string', 'regex:/^[0-9]{12}$/'],
            'mother_photo'           => 'nullable|image|max:3072',
            'guardian_photo'         => 'nullable|image|max:3072',
            'doc_birth_certificate'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_aadhaar'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_caste'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_tc'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_marksheet'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'doc_pan_id'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'additional_items'       => 'nullable|array',
            'additional_items.*'     => 'nullable|integer|min:0',
            'payment_verified'       => 'nullable',
            'payment_verified_at'    => 'nullable|string|max:100',
            'payment_verified_by'    => 'nullable|string|max:100',
            'payment_verification_notes' => 'nullable|string',
            'submission_token'       => 'nullable|string|max:100',
        ], [
            'first_name.required'       => 'Student First Name is required.',
            'first_name.min'            => 'Student First Name must be at least 2 characters.',
            'dob.required'              => 'Student Date of Birth is required.',
            'dob.before_or_equal'       => 'Student Date of Birth cannot be in the future.',
            'gender.required'           => 'Student Gender selection is required.',
            'gender.in'                 => 'Please select a valid Gender (Male, Female, or Other).',
            'class_id.required'         => 'Please select the Standard / Class applying for.',
            'class_id.exists'           => 'The selected Class does not exist.',
            'parent_name.required'      => 'Father / Primary Parent Name is required.',
            'parent_name.min'           => 'Father / Primary Parent Name must be at least 2 characters.',
            'parent_mobile.required'    => 'Father / Primary Parent Mobile number is required.',
            'parent_mobile.regex'       => 'Father Mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).',
            'mother_mobile.regex'       => 'Mother Mobile number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).',
            'guardian_mobile.regex'     => 'Guardian Mobile number must be a valid 10-digit mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).',
            'email.email'               => 'Student Email address must be a valid email format (e.g. student@example.com).',
            'parent_email.email'        => 'Father Email address must be a valid email format (e.g. father@example.com).',
            'mother_email.email'        => 'Mother Email address must be a valid email format (e.g. mother@example.com).',
            'aadhaar_no.regex'          => 'Student Aadhaar number must be exactly 12 numeric digits.',
            'father_aadhaar.regex'      => 'Father Aadhaar number must be exactly 12 numeric digits.',
            'pincode.regex'             => 'Pincode must be a valid 6-digit postal code (e.g. 600001).',
            'payment_terms.required'    => 'Payment Terms selection is required (Single Payment, 2 Terms, or 3 Terms).',
            'payment_mode.required'     => 'Payment Mode is required (UPI, Net Banking, or Cash).',
            'amount_collected.required' => 'Amount Collected Today is required (enter 0 if paying later).',
            'amount_collected.numeric'  => 'Amount Collected must be a valid number.',
            'amount_collected.min'      => 'Amount Collected cannot be negative.',
            'previous_percentage.between' => 'Previous Percentage / CGPA must be between 0 and 100.',
        ]);

        // Enforce uniqueness of Government Document numbers and student identity fields
        // (Student Aadhaar Number and EMIS/PEN Number cannot be reused across admissions)
        if (!empty($validated['aadhaar_no'])) {
            $existing = \App\Models\Student::findStudentByAadhaar($validated['aadhaar_no']);
            if ($existing) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'aadhaar_no' => "Student Aadhaar Number is already used for another admission: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']}). Government document numbers cannot be reused for another admission."
                ]);
            }
        }

        if (!empty($validated['emis_no'])) {
            $existing = \App\Models\Student::findStudentByEmis($validated['emis_no']);
            if ($existing) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'emis_no' => "EMIS / PEN Number is already used for another admission: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']}). Government document numbers cannot be reused for another admission."
                ]);
            }
        }

        if (!empty($validated['email'])) {
            $existing = \App\Models\Student::findStudentByEmail($validated['email']);
            if ($existing) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => "Student Email Address is already registered to student: {$existing['name']} (Admn: {$existing['admission_no']}, Class: {$existing['class']})."
                ]);
            }
        }

        $currentYear = AcademicYear::current();
        $feeData = $this->getFeeStructureData();
        $classFee = $feeData['standardFees'][$request->class_id] ?? null;

        // Calculate Fee Totals from official fee structure 2026-2027
        $isIntegrated = $request->boolean('is_integrated');
        $includeAdmissionFee = $request->has('include_admission_fee') ? $request->boolean('include_admission_fee') : true;

        if ($isIntegrated && !empty($classFee['has_integrated'])) {
            $term1Fee     = (float)($classFee['integrated_term1'] ?? 52500);
            $term2Fee     = (float)($classFee['integrated_term2'] ?? 32500);
            $term3Fee     = (float)($classFee['integrated_term3'] ?? 20000);
            $materialFee  = 0;
            $admissionFee = $includeAdmissionFee ? (float)($classFee['admission_fee'] ?? 2500) : 0;
            $basicTotal   = (float)($classFee['integrated_fee'] ?? 105000) + $admissionFee;
        } else {
            $term1Fee     = (float)($classFee['term1_fee'] ?? 25000);
            $term2Fee     = (float)($classFee['term2_fee'] ?? 11000);
            $term3Fee     = (float)($classFee['term3_fee'] ?? 11000);
            $materialFee  = (float)($classFee['material_fee'] ?? 0);
            $admissionFee = $includeAdmissionFee ? (float)($classFee['admission_fee'] ?? 2500) : 0;
            $basicTotal   = (float)($classFee['total_basic'] ?? ($term1Fee + $term2Fee + $term3Fee + $materialFee)) + $admissionFee;
        }

        // After School Program (ASP) Fee — replaces hostel per school requirement
        $isAsp = $request->boolean('is_asp');
        $aspFee = $isAsp ? (float)($request->asp_fee ?? 15000) : 0;

        // Transport Facility Fee — based on chosen Route & Stopping distance
        $transportFee = 0;
        if ($request->filled('transport_route_id')) {
            if ($request->filled('transport_stop_id')) {
                $stop = \App\Models\TransportStop::find($request->transport_stop_id);
                $transportFee = (float)($stop?->fare ?? $request->transport_fee ?? 0);
            } else {
                $route = \App\Models\TransportRoute::find($request->transport_route_id);
                $transportFee = (float)($route?->fee ?? $request->transport_fee ?? 0);
            }
        }

        // Note: Extra Curricular Activities (ECA) are complimentary activity choices without adding fee ("ECA - Remove payment")
        $activitiesFee = 0;

        // Warehouse Admission Kit Calculations & Stock Validation
        $kitConfigs = \App\Models\AdmissionKitConfig::with('item')
            ->where('class_id', $request->class_id)
            ->get();

        $standardKitFee = 0;
        $additionalInventoryFee = 0;
        $stockCheckErrors = [];
        $issuedItemsSummary = [];

        foreach ($kitConfigs as $cfg) {
            $item = $cfg->item;
            if (!$item || !$item->is_active) continue;

            $defaultQty = $cfg->default_quantity;
            $addQty = 0;
            if ($cfg->allow_additional_qty && $request->has("additional_items.{$cfg->item_id}")) {
                $addQty = max(0, (int)$request->input("additional_items.{$cfg->item_id}"));
            }
            $totalQty = $defaultQty + $addQty;

            $unitCharge = (float)($cfg->student_charge > 0 ? $cfg->student_charge : $item->student_price);
            $standardKitFee += $defaultQty * $unitCharge;
            $addCharge  = $addQty * $unitCharge;
            $additionalInventoryFee += $addCharge;

            // Check stock availability
            if ($item->current_stock < $totalQty) {
                $stockCheckErrors[] = "Insufficient stock for '{$item->name}'. Required: {$totalQty} {$item->unit}, Available in Warehouse: {$item->current_stock} {$item->unit}.";
            }

            $issuedItemsSummary[] = [
                'config'           => $cfg,
                'item'             => $item,
                'default_quantity' => $defaultQty,
                'add_quantity'     => $addQty,
                'total_quantity'   => $totalQty,
                'unit_charge'      => $unitCharge,
                'add_charge'       => $addCharge,
            ];
        }

        if (!empty($stockCheckErrors)) {
            return back()->withInput()->with('error', implode(' ', $stockCheckErrors) . ' Please replenish stock in Warehouse before completing admission.');
        }

        // Custom Kit Items & Textbooks added during admission
        $customKitFee = 0;
        $customKitItems = [];
        if ($request->filled('custom_kit_items')) {
            $rawCustom = $request->input('custom_kit_items');
            $customKitItems = is_string($rawCustom) ? json_decode($rawCustom, true) : $rawCustom;
            if (is_array($customKitItems)) {
                foreach ($customKitItems as $cItem) {
                    $cQty = max(1, (int)($cItem['quantity'] ?? 1));
                    $cPrice = max(0, (float)($cItem['unit_price'] ?? 0));
                    $customKitFee += $cQty * $cPrice;
                }
            }
        }

        // Subtotal before Concession: Tuition + ASP + Transport + Admission Kits (Standard + Additional + Custom)
        $totalKitFee = $standardKitFee + $additionalInventoryFee + $customKitFee;
        $subTotal = $basicTotal + $aspFee + $transportFee + $totalKitFee;

        // Concessions (Staff kid, Topper, Sports, Single shot, Referral, Sibling)
        $concessionAmount = max(0, min((float)($request->concession_amount ?? 0), $subTotal));
        $totalFee = max(0, $subTotal - $concessionAmount);
        $amountCollected = (float)$request->amount_collected;

        if ($amountCollected > $totalFee) {
            $amountCollected = $totalFee;
        }

        $pendingAmount = max(0, $totalFee - $amountCollected);
        $paymentDate = $request->payment_date ?: date('Y-m-d');
        $paymentMode = $request->payment_mode;
        $paymentTerms = $request->payment_terms;
        $remainingFees = max(0, $totalFee - $totalKitFee);

        $paymentAccount = $request->input('payment_account');
        if (!$paymentAccount) {
            if (strtolower($paymentMode) === 'upi') {
                $paymentAccount = 'upi';
            } elseif (str_contains(strtolower($paymentMode), 'box 2') || strtolower($paymentMode) === 'cash_box_2') {
                $paymentAccount = 'cash_box_2';
            } else {
                $paymentAccount = 'cash_box_1';
            }
        }

        // Term Breakdown Calculations
        $terms = [];
        if ($paymentTerms === 'single') {
            $status = $amountCollected >= $totalFee ? 'paid' : ($amountCollected > 0 ? 'partially_paid' : 'pending');
            $terms[] = [
                'term_number'  => 1,
                'name'         => 'Term 1',
                'amount'       => $totalFee,
                'paid'         => $amountCollected,
                'pending'      => $pendingAmount,
                'due_date'     => $paymentDate,
                'status'       => $status,
                'payment_mode' => $amountCollected > 0 ? $paymentMode : null,
                'payment_date' => $amountCollected > 0 ? $paymentDate : null,
            ];
        } elseif ($paymentTerms === '2_terms') {
            // 2 TERMS SPLIT: 100% Kit, T1(50%), Transport(50%) in Term 1; T2(50%), Transport(50%) in Term 2
            $transportT1 = (float)round($transportFee * 0.5, 2);
            $transportT2 = (float)round($transportFee - $transportT1, 2);

            $termFeeBase = max(0, $totalFee - $totalKitFee - $transportFee);
            $termFeeT1   = (float)round($termFeeBase * 0.5, 2);
            $termFeeT2   = (float)round($termFeeBase - $termFeeT1, 2);

            $t1Amount = (float)round($totalKitFee + $termFeeT1 + $transportT1, 2);
            $t2Amount = max(0, (float)round($totalFee - $t1Amount, 2));

            $t1Paid = min($amountCollected, $t1Amount);
            $t1Pending = max(0, $t1Amount - $t1Paid);
            $t1Status = $t1Paid >= $t1Amount ? 'paid' : ($t1Paid > 0 ? 'partially_paid' : 'pending');

            $t2Paid = max(0, $amountCollected - $t1Amount);
            $t2Pending = max(0, $t2Amount - $t2Paid);
            $t2Status = $t2Paid >= $t2Amount ? 'paid' : ($t2Paid > 0 ? 'partially_paid' : 'pending');

            $term2DueDate = $request->term_2_due_date ?: '2026-08-05';

            $terms[] = [
                'term_number'  => 1,
                'name'         => 'Term 1',
                'amount'       => $t1Amount,
                'paid'         => $t1Paid,
                'pending'      => $t1Pending,
                'due_date'     => $paymentDate,
                'status'       => $t1Status,
                'payment_mode' => $t1Paid > 0 ? $paymentMode : null,
                'payment_date' => $t1Paid > 0 ? $paymentDate : null,
            ];

            $terms[] = [
                'term_number'  => 2,
                'name'         => 'Term 2',
                'amount'       => $t2Amount,
                'paid'         => $t2Paid,
                'pending'      => $t2Pending,
                'due_date'     => $term2DueDate,
                'status'       => $t2Status,
                'payment_mode' => $t2Paid > 0 ? $paymentMode : null,
                'payment_date' => $t2Paid > 0 ? $paymentDate : null,
            ];
        } else { // 3_terms
            // 3 TERMS SPLIT: 100% Kit, T1 Fee + Transport(50%) in Term 1; T2 Fee + Transport(50%) in Term 2; T3 Fee in Term 3 (0% Transport)
            $transportT1 = (float)round($transportFee * 0.5, 2);
            $transportT2 = (float)round($transportFee - $transportT1, 2);

            $aspShare        = round($aspFee / 3, 2);
            $concessionShare = round($concessionAmount / 3, 2);

            $baseT1   = $term1Fee + $materialFee + $admissionFee;
            $t1Amount = max(0, (float)round($totalKitFee + $baseT1 + $transportT1 + $aspShare - $concessionShare, 2));

            $baseT2   = $term2Fee;
            $t2Amount = max(0, (float)round($baseT2 + $transportT2 + $aspShare - $concessionShare, 2));

            // Remaining balance assigned to Term 3 to guarantee exact total sum matching totalFee
            $t3Amount = max(0, (float)round($totalFee - ($t1Amount + $t2Amount), 2));

            $rem = $amountCollected;

            $t1Paid = min($rem, $t1Amount);
            $t1Pending = max(0, $t1Amount - $t1Paid);
            $t1Status = $t1Paid >= $t1Amount ? 'paid' : ($t1Paid > 0 ? 'partially_paid' : 'pending');
            $rem = max(0, $rem - $t1Amount);

            $t2Paid = min($rem, $t2Amount);
            $t2Pending = max(0, $t2Amount - $t2Paid);
            $t2Status = $t2Paid >= $t2Amount ? 'paid' : ($t2Paid > 0 ? 'partially_paid' : 'pending');
            $rem = max(0, $rem - $t2Amount);

            $t3Paid = min($rem, $t3Amount);
            $t3Pending = max(0, $t3Amount - $t3Paid);
            $t3Status = $t3Paid >= $t3Amount ? 'paid' : ($t3Paid > 0 ? 'partially_paid' : 'pending');

            $term2DueDate = $request->term_2_due_date ?: '2026-08-05';
            $term3DueDate = $request->term_3_due_date ?: '2026-12-05';

            $terms[] = [
                'term_number'  => 1,
                'name'         => 'Term 1',
                'amount'       => $t1Amount,
                'paid'         => $t1Paid,
                'pending'      => $t1Pending,
                'due_date'     => $paymentDate,
                'status'       => $t1Status,
                'payment_mode' => $t1Paid > 0 ? $paymentMode : null,
                'payment_date' => $t1Paid > 0 ? $paymentDate : null,
            ];

            $terms[] = [
                'term_number'  => 2,
                'name'         => 'Term 2',
                'amount'       => $t2Amount,
                'paid'         => $t2Paid,
                'pending'      => $t2Pending,
                'due_date'     => $term2DueDate,
                'status'       => $t2Status,
                'payment_mode' => $t2Paid > 0 ? $paymentMode : null,
                'payment_date' => $t2Paid > 0 ? $paymentDate : null,
            ];

            $terms[] = [
                'term_number'  => 3,
                'name'         => 'Term 3',
                'amount'       => $t3Amount,
                'paid'         => $t3Paid,
                'pending'      => $t3Pending,
                'due_date'     => $term3DueDate,
                'status'       => $t3Status,
                'payment_mode' => $t3Paid > 0 ? $paymentMode : null,
                'payment_date' => $t3Paid > 0 ? $paymentDate : null,
            ];
        }

        $overallStatus = $amountCollected >= $totalFee ? 'paid' : ($amountCollected > 0 ? 'partially_paid' : 'pending');

        $student = null;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('students/photos', 'public');
        }

        $fatherPhotoPath = null;
        if ($request->hasFile('father_photo')) {
            $fatherPhotoPath = $request->file('father_photo')->store('parents/photos', 'public');
        }

        $motherPhotoPath = null;
        if ($request->hasFile('mother_photo')) {
            $motherPhotoPath = $request->file('mother_photo')->store('parents/photos', 'public');
        }

        $guardianPhotoPath = null;
        if ($request->hasFile('guardian_photo')) {
            $guardianPhotoPath = $request->file('guardian_photo')->store('parents/photos', 'public');
        }

        try {
            DB::transaction(function () use ($validated, $currentYear, $totalFee, $amountCollected, $pendingAmount, $paymentMode, $paymentAccount, $paymentDate, $paymentTerms, $overallStatus, $terms, $issuedItemsSummary, $request, $photoPath, $fatherPhotoPath, $motherPhotoPath, $guardianPhotoPath, $isAsp, $aspFee, $transportFee, $concessionAmount, $customKitItems, &$student) {
                $studentFullName = trim($request->first_name . ' ' . ($request->last_name ?? ''));

            // Save or Update Enquiry
            $enquiryId = $request->input('enquiry_id');
            $existingEnquiry = $enquiryId ? Enquiry::find($enquiryId) : null;
            if ($existingEnquiry) {
                $enquiry = $existingEnquiry;
                $prevStatus = $existingEnquiry->status;
                $existingEnquiry->update([
                    'status'               => 'converted',
                    'converted_at'         => now(),
                    'converted_by'         => Auth::id(),
                    'payment_terms'        => $paymentTerms,
                    'total_admission_fee'  => $totalFee,
                    'amount_collected'     => $amountCollected,
                    'pending_amount'       => $pendingAmount,
                    'payment_mode'         => $paymentMode,
                    'payment_account'      => $paymentAccount,
                    'payment_date'         => $paymentDate,
                    'payment_status'       => $overallStatus,
                    'fee_breakdown'        => $terms,
                ]);
                EnquiryStatusHistory::create([
                    'enquiry_id'      => $existingEnquiry->id,
                    'previous_status' => $prevStatus,
                    'new_status'      => 'converted',
                    'reason'          => 'Admission Conversion Completed',
                    'remarks'         => "Converted to student record by " . (Auth::user()?->name ?? 'Staff'),
                    'changed_by'      => Auth::id(),
                ]);
            } else {
                $enquiry = Enquiry::create(array_merge($validated, [
                    'student_name'         => $studentFullName,
                    'enquiry_number'       => Enquiry::generateNumber(),
                    'status'               => 'converted',
                    'academic_year_id'     => $currentYear?->id,
                    'created_by'           => Auth::id(),
                    'converted_at'         => now(),
                    'converted_by'         => Auth::id(),
                    'father_name'          => $request->parent_name,
                    'father_mobile'        => $request->parent_mobile,
                    'father_occupation'    => $request->father_occupation,
                    'mother_name'          => $request->mother_name,
                    'mother_mobile'        => $request->mother_mobile,
                    'mother_occupation'    => $request->mother_occupation,
                    'last_school_studied'      => $request->previous_school_name ?: $request->previous_school,
                    'previous_school_attended' => $request->previous_school_name ?: $request->previous_school,
                    'last_class_studied'       => $request->previous_class,
                    'previous_class'           => $request->previous_class,
                    'board'                    => $request->previous_school_board ?: $request->board,
                    'previous_percentage'      => $request->previous_percentage ?: $request->previous_marks,
                    'year_of_passing'          => $request->year_of_passing,
                    'stream_group'             => $request->stream_group,
                    'referred_by'              => $request->referred_by ?? $request->source,
                    'dress_size'               => $request->dress_size,
                    'shoe_size'                => $request->shoe_size,
                    'second_language'          => $request->second_language,
                    'follow_up_remarks'        => $request->notes,
                    'payment_terms'        => $paymentTerms,
                    'total_admission_fee'  => $totalFee,
                    'amount_collected'     => $amountCollected,
                    'pending_amount'       => $pendingAmount,
                    'payment_mode'         => $paymentMode,
                    'payment_account'      => $paymentAccount,
                    'payment_date'         => $paymentDate,
                    'payment_status'       => $overallStatus,
                    'fee_breakdown'        => $terms,
                ]));
            }

            // Generate Official School Admission Number: Boys (EPSB0000 sample -> EPSB0001...), Girls (EPSG000 sample -> EPSG0001...)
            $admNo = \App\Models\Student::generateAdmissionNumber($request->gender);

            // Generate Formatted Roll Number using StudentRollNumberService (e.g. 6A011, LKGC018)
            $classModel = \App\Models\Classes::find($request->class_id);
            $sectionModel = $request->section_id ? \App\Models\Section::find($request->section_id) : \App\Models\Section::where('class_id', $request->class_id)->first();

            $cleanClassName = \App\Services\StudentRollNumberService::getClassCode($classModel);
            $cleanSecName   = \App\Services\StudentRollNumberService::getSectionCode($sectionModel);

            $classSecCount = \App\Models\StudentEnrollment::where('class_id', $request->class_id)
                ->when($sectionModel, fn($q) => $q->where('section_id', $sectionModel->id))
                ->count() + 1;

            $autoRollNo = \App\Services\StudentRollNumberService::formatRollNumber($cleanClassName, $cleanSecName, $classSecCount);

            // Auto Allocate House in Round-Robin Order (Red, Blue, Green, Yellow)
            $houses = ['Red', 'Blue', 'Green', 'Yellow'];
            $totalEnrolledCount = \App\Models\StudentEnrollment::count();
            $autoHouse = $houses[$totalEnrolledCount % count($houses)];

            // Statuses based on requireApprovalWorkflow (temporarily disabled per user request: direct active/enrolled)
            $studentStatus    = $this->requireApprovalWorkflow ? 'pending_principal' : 'active';
            $enrollmentStatus = $this->requireApprovalWorkflow ? 'pending' : 'active';
            $enquiryStatus    = $this->requireApprovalWorkflow ? 'pending_principal_approval' : 'enrolled';

            // Save Student with Parent Photos
            $student = \App\Models\Student::create([
                'enquiry_id'               => $enquiry->id,
                'admission_draft_id'       => $request->input('draft_id'),
                'admission_no'             => $admNo,
                'admission_date'           => $paymentDate,
                'roll_number'              => $autoRollNo,
                'first_name'               => $request->first_name,
                'last_name'                => $request->filled('last_name') ? $request->last_name : ($request->input('last_name') ?: '.'),
                'dob'                      => $request->dob,
                'gender'                   => $request->gender,
                'photo'                    => $photoPath,
                'blood_group'              => $request->blood_group ? substr($request->blood_group, 0, 5) : null,
                'category'                 => $request->filled('category') ? strtolower($request->category) : 'general',
                'religion'                 => $request->religion,
                'mother_tongue'            => $request->mother_tongue,
                'aadhaar_no'               => $request->aadhaar_no ? substr(preg_replace('/[^0-9]/', '', $request->aadhaar_no), 0, 12) : null,
                'pincode'                  => $request->pincode ? substr($request->pincode, 0, 10) : null,
                'mobile'                   => $request->parent_mobile ? substr($request->parent_mobile, 0, 15) : null,
                'email'                    => $request->email ?: $request->parent_email,
                'father_name'              => $request->parent_name,
                'father_mobile'            => $request->parent_mobile ? substr($request->parent_mobile, 0, 15) : null,
                'father_email'             => $request->parent_email,
                'father_occupation'        => $request->father_occupation,
                'father_qualification'     => $request->father_qualification,
                'father_income'            => $request->father_income,
                'father_photo'             => $fatherPhotoPath,
                'father_aadhaar'           => $request->father_aadhaar ? substr(preg_replace('/[^0-9]/', '', $request->father_aadhaar), 0, 12) : null,
                'mother_name'              => $request->mother_name,
                'mother_occupation'        => $request->mother_occupation,
                'mother_qualification'     => $request->mother_qualification,
                'mother_income'            => $request->mother_income,
                'mother_mobile'            => $request->mother_mobile ? substr($request->mother_mobile, 0, 15) : null,
                'mother_email'             => $request->mother_email,
                'mother_photo'             => $motherPhotoPath,
                'emergency_contact_name'   => $request->emergency_contact_name,
                'emergency_contact_mobile' => $request->emergency_contact_mobile,
                'guardian_name'            => $request->guardian_name,
                'guardian_mobile'          => $request->guardian_mobile ? substr($request->guardian_mobile, 0, 15) : null,
                'guardian_relation'        => $request->guardian_relation,
                'guardian_photo'           => $guardianPhotoPath,
                'parent_visitor_pass_token'=> \Illuminate\Support\Str::random(40),
                'annual_family_income'     => $request->annual_family_income,
                'residential_address'      => $request->address,
                'permanent_address'        => $request->address,
                'previous_school_name'     => $request->previous_school,
                'previous_percentage'      => $request->previous_percentage,
                'stream_group'             => $request->stream_group,
                'stream_group_allotted'    => $request->stream_group_allotted ?: $request->stream_group,
                'is_tc_enclosed'           => $request->boolean('is_tc_enclosed'),
                'is_qualified_promotion'   => $request->is_qualified_promotion,
                'year_of_passing'          => $request->year_of_passing,
                'status'                   => $studentStatus,
                'student_type'             => in_array($request->input('student_type'), ['day_scholar', 'hosteller', 'day_boarder']) ? $request->input('student_type') : 'day_scholar',
                'emis_no'                  => $request->emis_no,
                'identification_mark_1'    => $request->identification_mark_1,
                'identification_mark_2'    => $request->identification_mark_2,
                'is_asp'                   => $isAsp,
                'asp_fee'                  => $aspFee,
                'transport_route_id'       => $request->transport_route_id,
                'transport_stop_id'        => $request->transport_stop_id,
                'transport_distance_km'    => $request->transport_distance_km,
                'transport_fee'            => $transportFee,
                'concession_type'          => $request->concession_type !== 'none' ? $request->concession_type : null,
                'concession_amount'        => $concessionAmount,
                'concession_remarks'       => $request->concession_remarks,
                'sibling_name'             => $request->sibling_name,
                'sibling_admission_no'     => $request->sibling_admission_no,
                'sibling_class'            => $request->sibling_class,
                'documents_submitted'      => $request->documents_submitted ?? [],
                'selected_eca'             => $request->activities ?? [],
                'payment_terms'            => $paymentTerms,
                'total_admission_fee'      => $totalFee,
                'admission_paid_amount'    => $amountCollected,
                'admission_pending_amount' => $pendingAmount,
                'payment_mode'             => $paymentMode,
                'payment_account'          => $paymentAccount,
                'payment_date'             => $paymentDate,
                'payment_status'           => $overallStatus,
                'admission_fee_terms'      => $terms,
                'dress_size'               => $request->dress_size,
                'shoe_size'                => $request->shoe_size,
                'second_language'          => $request->second_language,
                'stream_group'             => $request->stream_group,
                'stream_group_allotted'    => $request->stream_group,
                'previous_school_name'     => $request->previous_school_name ?: $request->previous_school,
                'previous_school_board'    => $request->previous_school_board ?: $request->board,
                'previous_percentage'      => $request->previous_percentage ?: $request->previous_marks,
                'year_of_passing'          => $request->year_of_passing,
                'is_tc_enclosed'           => $request->boolean('is_tc_enclosed') || $request->is_tc_enclosed == '1',
                'is_qualified_promotion'   => $request->is_qualified_promotion ?? 'Yes',
                'tc_number'                => $request->tc_number,
                'tc_date'                  => $request->tc_date,
                'custom_kit_items'         => $customKitItems ?: null,
                'textbook_custom_fields'   => [
                    'second_language' => $request->second_language,
                    'textbook_pack'   => $request->textbook_pack,
                    'textbook_notes'  => $request->textbook_notes,
                ],
            ]);

            // Ensure enquiry references the newly admitted student
            $enquiry->update(['converted_student_id' => $student->id]);

            // Finalize Admission Draft if admission was resumed from draft
            if ($request->filled('draft_id')) {
                $completedDraft = AdmissionDraft::with('documents')->find($request->draft_id);
                if ($completedDraft) {
                    $completedDraft->update([
                        'status'               => 'submitted',
                        'converted_student_id' => $student->id,
                        'submission_error'     => null,
                    ]);

                    // Migrate draft documents to permanent student documents if not re-uploaded
                    foreach ($completedDraft->documents as $draftDoc) {
                        $alreadyUploaded = \App\Models\StudentDocument::where('student_id', $student->id)
                            ->where('document_type', $draftDoc->document_type)
                            ->exists();
                        if (!$alreadyUploaded && \Illuminate\Support\Facades\Storage::disk('public')->exists($draftDoc->stored_path)) {
                            $destPath = "students/{$student->id}/documents/" . basename($draftDoc->stored_path);
                            \Illuminate\Support\Facades\Storage::disk('public')->copy($draftDoc->stored_path, $destPath);
                            \App\Models\StudentDocument::create([
                                'student_id'    => $student->id,
                                'document_type' => $draftDoc->document_type,
                                'file_path'     => $destPath,
                                'original_name' => $draftDoc->original_filename,
                                'status'        => 'verified',
                                'verified_by'   => Auth::id(),
                                'verified_at'   => now(),
                                'remarks'       => 'Transferred from Admission Draft',
                            ]);
                        }
                    }
                }
            }

            // Save Student Enrollment
            \App\Models\StudentEnrollment::create([
                'student_id'       => $student->id,
                'class_id'         => $request->class_id,
                'section_id'       => $sectionModel?->id,
                'academic_year_id' => $currentYear?->id,
                'roll_number'      => $autoRollNo,
                'house'            => $autoHouse,
                'status'           => $enrollmentStatus,
                'enrollment_date'  => $paymentDate,
            ]);

            // Create Transport Allotment if transport facility is selected
            if ($request->filled('transport_route_id')) {
                $routeId = (int) $request->transport_route_id;
                $stopId = $request->filled('transport_stop_id') 
                    ? (int) $request->transport_stop_id 
                    : (\App\Models\TransportStop::where('route_id', $routeId)->where('is_active', true)->orderBy('stop_order')->value('id'));

                if ($stopId) {
                    try {
                        $transportService = app(\App\Services\TransportManagementService::class);
                        $transportService->assignStudentTransport($student->id, [
                            'route_id'              => $routeId,
                            'stop_id'               => $stopId,
                            'drop_stop_id'          => $request->filled('transport_drop_stop_id') ? (int) $request->transport_drop_stop_id : $stopId,
                            'pickup_time'           => $request->input('transport_pickup_time'),
                            'drop_time'             => $request->input('transport_drop_time'),
                            'fee'                   => $transportFee,
                            'start_date'            => $paymentDate,
                            'remarks'               => 'Assigned during New Admission',
                        ], Auth::id());
                    } catch (\Throwable $te) {
                        \Illuminate\Support\Facades\Log::warning("Transport allotment during admission warning: " . $te->getMessage());
                    }
                }
            }

            // Direct document file uploads processing
            $docInputs = [
                'doc_birth_certificate' => 'birth_certificate',
                'doc_aadhaar'           => 'aadhaar',
                'doc_caste'             => 'caste',
                'doc_tc'                => 'tc',
                'doc_marksheet'         => 'marksheet',
                'doc_pan_id'            => 'pan',
            ];
            foreach ($docInputs as $inputKey => $docType) {
                if ($request->hasFile($inputKey)) {
                    $docFile = $request->file($inputKey);
                    $docStored = $docFile->storeAs("students/{$student->id}/documents", $docType . '_' . time() . '.' . $docFile->getClientOriginalExtension(), 'public');
                    \App\Models\StudentDocument::create([
                        'student_id'    => $student->id,
                        'document_type' => $docType,
                        'file_path'     => $docStored,
                        'original_name' => $docFile->getClientOriginalName(),
                        'status'        => 'verified',
                        'verified_by'   => Auth::id(),
                        'verified_at'   => now(),
                        'remarks'       => 'Desk verified during admission entry',
                    ]);
                }
            }

            // Save Student Fee Charge so Fee Status displays Total Billed accurately
            DB::table('student_fee_charges')->insert([
                'student_id'       => $student->id,
                'academic_year_id' => $currentYear?->id,
                'amount'           => $totalFee,
                'due_date'         => $paymentDate,
                'description'      => 'Annual Admission Fee Charge',
                'source'           => 'admission',
                'is_active'        => true,
                'created_by'       => Auth::id(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Log Fee Payment if amount collected > 0
            if ($amountCollected > 0) {
                $feePaymentMode = match(strtolower($paymentMode)) {
                    'upi' => 'upi',
                    'net banking', 'online' => 'online',
                    default => 'cash',
                };

                $feeRemarks = 'Admission Fee Payment for ' . ($classModel?->name ? 'Class ' . $classModel->name : 'New Admission') . ' (' . str_replace('_', ' ', strtoupper($paymentTerms)) . ')';
                if ($request->filled('payment_verified') && $request->payment_verified) {
                    $vBy = $request->input('payment_verified_by') ?: (Auth::user()?->name ?? 'Admin');
                    $vAt = $request->input('payment_verified_at') ?: now()->toDayDateTimeString();
                    $feeRemarks .= " [VERIFIED by {$vBy} at {$vAt}]";
                }

                \App\Models\FeePayment::create([
                    'student_id'       => $student->id,
                    'academic_year_id' => $currentYear?->id,
                    'receipt_number'   => 'REC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'payment_date'     => $paymentDate,
                    'amount'           => $totalFee,
                    'amount_paid'      => $amountCollected,
                    'total_paid'       => $amountCollected,
                    'payment_mode'     => $feePaymentMode,
                    'payment_account'  => $paymentAccount,
                    'term_name'        => 'Admission Fee',
                    'transaction_id'   => $request->input('transaction_id') ?: $request->input('upi_ref_no'),
                    'remarks'          => $feeRemarks,
                    'collected_by'     => Auth::id(),
                ]);
            }

            // Automatic Stock Deduction & Inventory Issue Records
            foreach ($issuedItemsSummary as $sItem) {
                $item       = $sItem['item'];
                $totalQty   = $sItem['total_quantity'];
                $defaultQty = $sItem['default_quantity'];
                $addQty     = $sItem['add_quantity'];
                $unitCharge = $sItem['unit_charge'];
                $addCharge  = $sItem['add_charge'];

                $prevStock = $item->current_stock;
                $newStock  = $prevStock - $totalQty;
                $unitCost  = $item->effective_purchase_cost;

                // Deduct stock from Academic Inventory
                $item->update(['current_stock' => $newStock]);

                // Create permanent Inventory Transaction
                $txn = \App\Models\InventoryTransaction::create([
                    'transaction_code' => \App\Models\InventoryTransaction::generateTransactionCode('ADM'),
                    'item_id'          => $item->id,
                    'transaction_type' => 'admission_issue',
                    'quantity'         => $totalQty,
                    'previous_stock'   => $prevStock,
                    'quantity_changed' => -$totalQty,
                    'new_stock'        => $newStock,
                    'unit_cost'        => $unitCost,
                    'total_cost'       => $totalQty * $unitCost,
                    'reference_type'   => 'admission',
                    'reference_id'     => $student->id,
                    'notes'            => "Issued upon Student Admission #{$student->admission_no} for Class {$classModel?->name} (Default: {$defaultQty}, Additional: {$addQty})",
                    'performed_by'     => Auth::id(),
                ]);

                // Record link between Admission and Inventory Issue
                \App\Models\AdmissionInventoryIssue::create([
                    'enquiry_id'               => $enquiry->id,
                    'student_id'               => $student->id,
                    'academic_year_id'         => $currentYear?->id,
                    'item_id'                  => $item->id,
                    'default_quantity'         => $defaultQty,
                    'additional_quantity'      => $addQty,
                    'total_quantity'           => $totalQty,
                    'unit_charge'              => $unitCharge,
                    'additional_charge'        => $addCharge,
                    'inventory_transaction_id' => $txn->id,
                ]);
            }

            // Flag Enquiry as issued & enrolled
            $enquiry->update([
                'is_inventory_issued' => true,
                'status'              => $enquiryStatus,
            ]);

            // Save immutable Books & Notes checklist snapshot for this admission
            try {
                $bookNoteService = app(\App\Services\BookNoteService::class);
                $bookNoteGroupId = null;
                if ($request->filled('book_note_group_id')) {
                    $bookNoteGroupId = (int) $request->book_note_group_id;
                } elseif ($request->filled('academic_group_id')) {
                    $bookNoteGroupId = (int) $request->academic_group_id;
                } elseif ($request->filled('stream_group')) {
                    $stStr = strtoupper($request->stream_group);
                    if (str_contains($stStr, 'ACCOUNT') || str_contains($stStr, 'COMMERCE') || str_contains($stStr, 'GROUP C')) {
                        $bookNoteGroupId = \App\Models\AcademicGroup::where('code', 'ACCOUNTS')->value('id');
                    } elseif (str_contains($stStr, 'CS') || str_contains($stStr, 'COMPUTER')) {
                        $bookNoteGroupId = \App\Models\AcademicGroup::where('code', 'CS')->value('id');
                    } elseif (str_contains($stStr, 'BIO') || str_contains($stStr, 'GROUP A') || str_contains($stStr, 'GROUP B')) {
                        $bookNoteGroupId = \App\Models\AcademicGroup::where('code', 'BIO')->value('id') ?? \App\Models\AcademicGroup::where('code', 'BIO_CS')->value('id');
                    }
                }

                $targetYearId = $request->filled('academic_year_id') ? (int)$request->academic_year_id : ($currentYear?->id ?? 0);
                $issuedIds = $request->input('issued_book_note_ids');
                $studentGender = $request->input('gender') ?: $student->gender;
                if ($targetYearId && $request->class_id) {
                    $bookNoteService->snapshotForAdmission(
                        $targetYearId,
                        (int)$request->class_id,
                        $bookNoteGroupId,
                        $student->id,
                        $student->id,
                        $enquiry->id,
                        is_array($issuedIds) ? $issuedIds : null,
                        \Illuminate\Support\Facades\Auth::id(),
                        $studentGender
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Books & Notes admission snapshot error: " . $e->getMessage());
            }
        });
        } catch (\Throwable $e) {
            if ($request->filled('draft_id')) {
                $failedDraft = AdmissionDraft::find($request->draft_id);
                if ($failedDraft) {
                    $failedDraft->update([
                        'status'                  => 'submission_failed',
                        'submission_attempted_at' => now(),
                        'submission_error'        => $e->getMessage(),
                    ]);
                }
            }
            \Illuminate\Support\Facades\Log::error('Admission enrollment error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->withInput()->withErrors([
                'general' => 'Admission could not be processed due to a server error: ' . $e->getMessage() . '. All your inputs have been retained. Please review and try submitting again.'
            ]);
        }

        $successMsg = $this->requireApprovalWorkflow
            ? 'Admission application submitted successfully! It has been forwarded to the Principal for formal review and approval.'
            : 'Student admitted and enrolled successfully! Admission slip and register entry created.';

        if ($submissionToken && isset($student?->id)) {
            Cache::put("admission_sub_{$submissionToken}", $student->id, 180);
        }

        return redirect()->route('admissions.submission-summary', $student->id)
            ->with('success', $successMsg);
    }

    /**
     * Post-submission summary with home QR document upload slip
     */
    public function submissionSummary(int $id)
    {
        $student = \App\Models\Student::with([
            'currentEnrollment.class',
            'currentEnrollment.section',
            'documents',
            'feePayments'
        ])->findOrFail($id);

        $docUploadUrl = route('public.student.documents', ['token' => $student->document_token]);
        $docUploadQrSvg = '';
        try {
            $docUploadQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(160)->margin(1)->generate($docUploadUrl);
        } catch (\Throwable $e) {
            $docUploadQrSvg = '';
        }

        return view('admissions.submission-summary', compact('student', 'docUploadQrSvg', 'docUploadUrl'));
    }

    /**
     * 2-Tier Admission Approvals Dashboard (Principal & Admin)
     */
    public function approvals(Request $request)
    {
        $tab = $request->get('tab');
        if (!$tab) {
            $tab = (Auth::user() && Auth::user()->hasRole('principal')) ? 'principal' : 'principal';
        }

        $pendingPrincipalCount = \App\Models\Student::where('status', 'pending_principal')->count();
        $pendingAdminCount     = \App\Models\Student::where('status', 'principal_approved')->count();
        $activeCount           = \App\Models\Student::where('status', 'active')->count();
        $rejectedCount         = \App\Models\Student::where('status', 'rejected')->count();

        $query = \App\Models\Student::with([
            'currentEnrollment.class',
            'currentEnrollment.section',
            'documents',
            'principalApprover',
            'adminApprover',
            'feePayments',
        ]);

        switch ($tab) {
            case 'admin':
                $query->where('status', 'principal_approved');
                break;
            case 'active':
                $query->where('status', 'active');
                break;
            case 'rejected':
                $query->where('status', 'rejected');
                break;
            case 'principal':
            default:
                $query->where('status', 'pending_principal');
                $tab = 'principal';
                break;
        }

        $students = $query->latest()->paginate(20);

        return view('admissions.approvals', compact(
            'students', 'tab', 'pendingPrincipalCount', 'pendingAdminCount', 'activeCount', 'rejectedCount'
        ));
    }

    /**
     * Principal endorses and approves admission application
     */
    public function principalApprove(Request $request, int $id)
    {
        if (!Auth::user()->hasRole('principal') && !Auth::user()->hasRole('super_admin') && !Auth::user()->can('approve admissions')) {
            abort(403, 'Unauthorized. Principal permission is required to approve admissions.');
        }

        $student = \App\Models\Student::findOrFail($id);
        $student->update([
            'status'                => 'principal_approved',
            'principal_approved_at' => now(),
            'principal_approved_by' => Auth::id(),
            'principal_notes'       => $request->notes ?? 'Approved by Principal for final enrollment',
        ]);

        \App\Models\Enquiry::where('student_name', $student->full_name)
            ->orWhere('enquiry_number', $student->admission_no)
            ->update(['status' => 'principal_approved']);

        return back()->with('success', "Admission application for {$student->full_name} has been APPROVED by the Principal. It is now awaiting final Admin confirmation.");
    }

    /**
     * Admin grants final confirmation: activates student, ID Card, and register
     */
    public function adminConfirm(Request $request, int $id)
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin') && !Auth::user()->can('approve admissions')) {
            abort(403, 'Unauthorized. Admin permission is required to finalize admissions.');
        }

        $student = \App\Models\Student::findOrFail($id);
        $student->update([
            'status'            => 'active',
            'admin_approved_at' => now(),
            'admin_approved_by' => Auth::id(),
            'admin_notes'       => $request->notes ?? 'Final admission confirmed by Admin',
        ]);

        // Activate Student Enrollment
        \App\Models\StudentEnrollment::where('student_id', $student->id)
            ->update(['status' => 'active']);

        // Finalize Enquiry status
        \App\Models\Enquiry::where('student_name', $student->full_name)
            ->orWhere('enquiry_number', $student->admission_no)
            ->update(['status' => 'converted']);

        return back()->with('success', "Admission for {$student->full_name} is officially CONFIRMED! Student is now active on the general register with unlocked ID Card & TC certificate.");
    }

    /**
     * Reject admission application with reason
     */
    public function rejectApplication(Request $request, int $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $student = \App\Models\Student::findOrFail($id);

        $student->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_by'      => Auth::id(),
            'rejected_at'      => now(),
        ]);

        \App\Models\StudentEnrollment::where('student_id', $student->id)
            ->update(['status' => 'rejected']);

        return back()->with('success', "Admission application for {$student->full_name} has been marked as rejected.");
    }

    public function show(int $id)
    {
        $enquiry  = Enquiry::with(['class', 'followUps.createdBy', 'createdBy'])->findOrFail($id);
        $classes  = Classes::active()->get();
        return view('admissions.show', compact('enquiry', 'classes'));
    }

    public function edit(int $id)
    {
        $enquiry      = Enquiry::findOrFail($id);
        $classes      = Classes::active()->get();
        $academicYear = AcademicYear::current();
        $users        = \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admissions.edit', compact('enquiry', 'classes', 'academicYear', 'users'));
    }

    public function update(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);

        if ($request->filled('parent_mobile')) {
            $cleaned = preg_replace('/[^\d]/', '', (string)$request->input('parent_mobile'));
            if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
                $cleaned = substr($cleaned, 2);
            }
            $request->merge(['parent_mobile' => $cleaned]);
        }

        $validated = $request->validate([
            'student_name'    => 'required|string|max:100',
            'dob'             => 'nullable|date|before:today',
            'gender'          => 'nullable|in:male,female,other',
            'class_id'        => 'required|exists:classes,id',
            'parent_name'     => 'required|string|max:100',
            'parent_mobile'   => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'parent_email'    => 'nullable|email|max:100',
            'address'         => 'nullable|string|max:255',
            'source'          => 'nullable|string|max:50',
            'notes'           => 'nullable|string',
            'follow_up_date'  => 'nullable|date',
            'previous_school' => 'nullable|string|max:150',
            'previous_class'  => 'nullable|string|max:50',
            'previous_percentage' => 'nullable|numeric|between:0,100',
            'assigned_to'         => 'nullable|exists:users,id',
        ]);

        $enquiry->update($validated);

        return redirect()->route('admissions.show', $enquiry->id)
            ->with('success', 'Enquiry updated successfully.');
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->can('delete admissions'), 403);
        $enquiry = Enquiry::findOrFail($id);
        $enquiry->delete();
        return redirect()->route('admissions.index')
            ->with('success', 'Enquiry deleted.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status'          => 'required|in:new,follow_up,converted,lost',
            'notes'           => 'nullable|string',
            'follow_up_date'  => 'nullable|date',
        ]);

        $enquiry = Enquiry::findOrFail($id);
        $enquiry->update([
            'status'         => $request->status,
            'follow_up_date' => $request->follow_up_date,
        ]);

        if ($request->notes) {
            EnquiryFollowUp::create([
                'enquiry_id'          => $enquiry->id,
                'notes'               => $request->notes,
                'next_follow_up_date' => $request->follow_up_date,
                'status'              => $request->status,
                'created_by'          => Auth::id(),
            ]);
        }

        return back()->with('success', 'Status updated to ' . $request->status . '.');
    }

    public function enquiryForm()
    {
        $classes      = Classes::active()->get();
        $academicYear = AcademicYear::current();
        return view('admissions.public-enquiry', compact('classes', 'academicYear'));
    }

    public function submitEnquiry(Request $request)
    {
        // Clean mobile numbers
        foreach (['parent_mobile', 'father_mobile', 'mother_mobile'] as $mobField) {
            if ($request->filled($mobField)) {
                $cleaned = preg_replace('/[^\d]/', '', (string)$request->input($mobField));
                if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
                    $cleaned = substr($cleaned, 2);
                }
                $request->merge([$mobField => $cleaned]);
            }
        }

        // Fallbacks for parent_name and parent_mobile from father/mother fields
        if (!$request->filled('parent_name')) {
            $request->merge(['parent_name' => $request->father_name ?: ($request->mother_name ?: 'Parent')]);
        }
        if (!$request->filled('parent_mobile')) {
            $request->merge(['parent_mobile' => $request->father_mobile ?: $request->mother_mobile]);
        }

        $validated = $request->validate([
            'student_name'          => 'required|string|max:100',
            'class_id'              => 'required|exists:classes,id',
            'dob'                   => 'nullable|date|before:today',
            'last_school_studied'   => 'nullable|string|max:150',
            'parent_name'           => 'required|string|max:100',
            'parent_mobile'         => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'parent_email'          => 'nullable|email|max:100',
            'father_name'           => 'nullable|string|max:100',
            'father_qualification'  => 'nullable|string|max:100',
            'father_occupation'     => 'nullable|string|max:100',
            'father_income'         => 'nullable|string|max:100',
            'father_mobile'         => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'mother_name'           => 'nullable|string|max:100',
            'mother_qualification'  => 'nullable|string|max:100',
            'mother_occupation'     => 'nullable|string|max:100',
            'mother_income'         => 'nullable|string|max:100',
            'mother_mobile'         => ['nullable', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'referred_by'           => 'nullable|string|max:100',
            'address'               => 'nullable|string|max:500',
            'documents.*'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'parent_mobile.regex' => 'Parent Mobile number must be a valid 10-digit Indian mobile number (e.g. 9876543210).',
            'father_mobile.regex' => 'Father Mobile number must be a valid 10-digit Indian mobile number.',
            'mother_mobile.regex' => 'Mother Mobile number must be a valid 10-digit Indian mobile number.',
        ]);
        $currentYear = AcademicYear::current();

        $docPaths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $docPaths[] = $file->store('enquiry-docs', 'public');
            }
        }

        $enquiry = Enquiry::create(array_merge($validated, [
            'enquiry_number'         => Enquiry::generateNumber(),
            'status'                 => 'new',
            'academic_year_id'       => $currentYear?->id,
            'previous_school'        => $request->last_school_studied ?: $request->previous_school,
            'source'                 => $request->source ?? 'website',
            'referral_name'          => $request->referred_by ?: $request->referral_name,
            'documents'              => $docPaths ?: null,
        ]));

        // Notify admin
        $adminEmail = \App\Models\SchoolSetting::get('admin_notification_email', config('mail.from.address'));
        if ($adminEmail) {
            try {
                \Illuminate\Support\Facades\Mail::to($adminEmail)
                    ->send(new \App\Mail\NewEnquiryNotification($enquiry->load('class')));
            } catch (\Exception $e) {
                // Fail silently — don't block enquiry submission
            }
        }

        return back()->with('success', 'Thank you! Your enquiry number is ' . $enquiry->enquiry_number);
    }

    public function pipeline(Request $request)
    {
        $stages = ['enquiry', 'application', 'entrance_test', 'interview', 'document_verification', 'confirmed', 'enrolled', 'rejected'];
        $currentStage = $request->stage ?? 'enquiry';
        $pipeline     = collect($stages)->mapWithKeys(fn($s) => [$s => Enquiry::where('status', $s)->count()]);
        $enquiries    = Enquiry::with('class')->where('status', $currentStage)->latest()->paginate(20);
        return view('admissions.pipeline', compact('stages', 'currentStage', 'pipeline', 'enquiries'));
    }

    public function advanceStage(Request $request, int $id)
    {
        $stages  = ['enquiry', 'application', 'entrance_test', 'interview', 'document_verification', 'confirmed', 'enrolled'];
        $enquiry = Enquiry::findOrFail($id);
        $idx     = array_search($enquiry->status, $stages);
        if ($idx === false || $idx >= count($stages) - 1) {
            return back()->with('error', 'Cannot advance stage further.');
        }
        $nextStage = $stages[$idx + 1];

        // Over-admission check when confirming
        if ($nextStage === 'confirmed' && $enquiry->class_id) {
            $currentYear = AcademicYear::current();
            $totalCapacity = SeatCapacity::where('class_id', $enquiry->class_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->sum('total_seats');
            $filled = SeatCapacity::where('class_id', $enquiry->class_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->sum('filled_seats');
            $confirmed = Enquiry::where('class_id', $enquiry->class_id)
                ->whereIn('status', ['confirmed', 'enrolled'])
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->count();
            if ($totalCapacity > 0 && ($filled + $confirmed) >= $totalCapacity && !$request->boolean('override')) {
                return back()->with('error', "Seat limit reached for this class ({$totalCapacity} total). Add to waitlist or use override.");
            }
        }

        $enquiry->update(['status' => $nextStage]);
        return back()->with('success', 'Stage advanced to ' . ucwords(str_replace('_', ' ', $nextStage)) . '.');
    }

    public function seats(Request $request)
    {
        $classes      = Classes::active()->get();
        $academicYear = AcademicYear::current();
        $seats        = SeatCapacity::with('class')
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->get();
        return view('admissions.seats', compact('classes', 'seats', 'academicYear'));
    }

    public function saveSeats(Request $request)
    {
        $currentYear = AcademicYear::current();

        if ($request->has('seats') && is_array($request->seats)) {
            // Bulk array form
            DB::transaction(function () use ($request, $currentYear) {
                foreach ($request->seats as $classId => $data) {
                    SeatCapacity::updateOrCreate(
                        ['class_id' => $classId, 'academic_year_id' => $currentYear?->id, 'category' => $data['category'] ?? 'general'],
                        ['total_seats' => $data['total'] ?? 0, 'filled_seats' => $data['filled'] ?? 0]
                    );
                }
            });
        } else {
            // Single-entry form
            $request->validate([
                'class_id'    => 'required|exists:classes,id',
                'category'    => 'required|string',
                'total_seats' => 'required|integer|min:0',
            ]);
            SeatCapacity::updateOrCreate(
                ['class_id' => $request->class_id, 'academic_year_id' => $currentYear?->id, 'category' => $request->category],
                ['total_seats' => $request->total_seats]
            );
        }

        return back()->with('success', 'Seat capacities saved.');
    }

    public function bulkImport(Request $request)
    {
        $classes = Classes::active()->get();
        return view('admissions.bulk-import', compact('classes'));
    }

    public function importTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="admission_enquiry_import_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'student_name',
                'parent_name',
                'parent_mobile',
                'parent_email',
                'class',
                'source',
                'notes',
            ]);
            fputcsv($handle, [
                'Arun Kumar',
                'Ramesh Kumar',
                '9876543210',
                'ramesh@example.com',
                'Grade 1',
                'walk_in',
                'Interested in CBSE curriculum',
            ]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function processBulkImport(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:2048']);

        $file        = $request->file('file');
        $ext         = strtolower($file->getClientOriginalExtension());
        $currentYear = AcademicYear::current();
        $imported    = 0;
        $errors      = [];

        if ($ext === 'csv') {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_map('trim', array_shift($rows));
        } else {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $header = array_map('trim', array_shift($sheet));
            $rows = $sheet;
        }

        // Normalize header keys
        $header = array_map(fn($h) => strtolower(str_replace([' ', '-'], '_', $h)), $header);

        foreach ($rows as $i => $row) {
            if (empty(array_filter($row))) continue;
            $data = array_combine($header, array_pad($row, count($header), null));

            $name = trim($data['student_name'] ?? $data['name'] ?? '');
            if (!$name) { $errors[] = "Row " . ($i + 2) . ": missing student name"; continue; }

            $classId = null;
            if (!empty($data['class_id'])) {
                $classId = (int) $data['class_id'];
            } elseif (!empty($data['class'])) {
                $cls = Classes::where('name', 'like', '%' . trim($data['class']) . '%')->first();
                $classId = $cls?->id;
            }
            if (!$classId) { $errors[] = "Row " . ($i + 2) . ": invalid class"; continue; }

            try {
                Enquiry::create([
                    'enquiry_number'  => Enquiry::generateNumber(),
                    'student_name'    => $name,
                    'parent_name'     => $data['parent_name'] ?? $data['father_name'] ?? '',
                    'parent_mobile'   => $data['parent_mobile'] ?? $data['mobile'] ?? '',
                    'parent_email'    => $data['parent_email'] ?? $data['email'] ?? null,
                    'class_id'        => $classId,
                    'source'          => $data['source'] ?? 'bulk_import',
                    'notes'           => $data['notes'] ?? $data['remarks'] ?? null,
                    'status'          => 'new',
                    'academic_year_id'=> $currentYear?->id,
                    'created_by'      => auth()->id(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        $msg = "Imported {$imported} enquiries successfully.";
        if ($errors) $msg .= ' ' . count($errors) . ' rows skipped: ' . implode('; ', array_slice($errors, 0, 3));
        return back()->with('success', $msg);
    }

    public function analytics(Request $request)
    {
        $currentYear = AcademicYear::current();
        $bySource    = Enquiry::select('source', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('source')->pluck('total', 'source');
        $byStatus    = Enquiry::select('status', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('status')->pluck('total', 'status');
        $byClass     = Enquiry::with('class')->select('class_id', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('class_id')->get();
        $monthlyTrend = Enquiry::select(DB::raw('MONTH(created_at) as m'), DB::raw('COUNT(*) as total'))
            ->whereYear('created_at', now()->year)
            ->groupBy('m')->orderBy('m')->pluck('total', 'm');

        // Counsellor-wise conversion
        $byCounsellor = Enquiry::select('assigned_to', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status IN (\'confirmed\',\'enrolled\') THEN 1 ELSE 0 END) as converted'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->whereNotNull('assigned_to')
            ->with('assignedTo')
            ->groupBy('assigned_to')->get();

        // Year-over-year
        $prevYear = AcademicYear::where('id', '<', $currentYear?->id)->orderByDesc('id')->first();
        $yoyData  = null;
        if ($currentYear && $prevYear) {
            $yoyData = [
                'current' => ['year' => $currentYear->name, 'enquiries' => Enquiry::where('academic_year_id', $currentYear->id)->count(), 'converted' => Enquiry::where('academic_year_id', $currentYear->id)->where('status', 'converted')->count()],
                'prev'    => ['year' => $prevYear->name, 'enquiries' => Enquiry::where('academic_year_id', $prevYear->id)->count(), 'converted' => Enquiry::where('academic_year_id', $prevYear->id)->where('status', 'converted')->count()],
            ];
        }

        return view('admissions.analytics', compact('bySource', 'byStatus', 'byClass', 'monthlyTrend', 'currentYear', 'yoyData', 'byCounsellor'));
    }

    public function exportEnquiries(Request $request)
    {
        $filters = $request->only(['status', 'class_id', 'search']);
        $filename = 'enquiries_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new EnquiriesExport($filters), $filename);
    }

    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'exists:enquiries,id',
            'status' => 'required|in:new,follow_up,converted,lost',
        ]);

        Enquiry::whereIn('id', $request->ids)->update(['status' => $request->status]);

        return back()->with('success', count($request->ids) . ' enquiries updated to ' . $request->status . '.');
    }

    public function applicationForm()
    {
        $classes      = Classes::active()->get();
        $academicYear = AcademicYear::current();
        return view('admissions.application-form', compact('classes', 'academicYear'));
    }

    public function storeApplication(Request $request)
    {
        if ($request->filled('parent_mobile')) {
            $cleaned = preg_replace('/[^\d]/', '', (string)$request->input('parent_mobile'));
            if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
                $cleaned = substr($cleaned, 2);
            }
            $request->merge(['parent_mobile' => $cleaned]);
        }

        $validated = $request->validate([
            'student_name'  => 'required|string|max:100',
            'dob'           => 'nullable|date|before:today',
            'gender'        => 'nullable|in:male,female,other',
            'class_id'      => 'required|exists:classes,id',
            'parent_name'   => 'required|string|max:100',
            'parent_mobile' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'parent_email'  => 'nullable|email|max:100',
            'address'       => 'nullable|string|max:500',
            'previous_school' => 'nullable|string|max:150',
        ], [
            'parent_mobile.regex' => 'Parent Mobile number must be a valid 10-digit Indian mobile number (e.g. 9876543210).',
        ]);

        // Check duplicate
        $dup = Enquiry::where('parent_mobile', $validated['parent_mobile'])
            ->where('class_id', $validated['class_id'])
            ->whereYear('created_at', now()->year)
            ->first();
        if ($dup) {
            return back()->withInput()->with('error', 'An enquiry with the same mobile and class already exists: ' . $dup->enquiry_number);
        }

        $currentYear = AcademicYear::current();
        $enquiry = Enquiry::create(array_merge($validated, [
            'enquiry_number'   => Enquiry::generateNumber(),
            'status'           => 'application',
            'source'           => 'application_form',
            'academic_year_id' => $currentYear?->id,
        ]));

        return back()->with('success', 'Application submitted! Reference number: ' . $enquiry->enquiry_number);
    }

    public function confirmationLetter(int $id)
    {
        $enquiry = Enquiry::with(['class', 'academicYear'])->findOrFail($id);
        $school  = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.admission-confirmation', compact('enquiry', 'school'));
        return $pdf->stream('admission-confirmation-' . $enquiry->enquiry_number . '.pdf');
    }

    public function rejectAdmission(Request $request, int $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        Enquiry::findOrFail($id)->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);
        return back()->with('success', 'Admission rejected with reason captured.');
    }

    public function waitlist(Request $request)
    {
        $currentYear = AcademicYear::current();
        $waitlisted  = Enquiry::where('status', 'waitlisted')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->with('class')->orderBy('waitlist_position')->get();
        return view('admissions.waitlist', compact('waitlisted', 'currentYear'));
    }

    public function addToWaitlist(Request $request, int $id)
    {
        $currentYear = AcademicYear::current();
        $maxPos = Enquiry::where('status', 'waitlisted')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->max('waitlist_position') ?? 0;
        Enquiry::findOrFail($id)->update([
            'status'            => 'waitlisted',
            'waitlist_position' => $maxPos + 1,
        ]);
        return back()->with('success', 'Added to waitlist at position ' . ($maxPos + 1) . '.');
    }

    public function promoteFromWaitlist(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);
        $enquiry->update(['status' => 'confirmed', 'waitlist_position' => null]);
        // Shift remaining waitlist positions up
        Enquiry::where('status', 'waitlisted')
            ->where('waitlist_position', '>', $enquiry->waitlist_position)
            ->decrement('waitlist_position');
        return back()->with('success', 'Promoted from waitlist to confirmed.');
    }

    public function saveEntranceTest(Request $request, int $id)
    {
        $request->validate([
            'entrance_test_date'        => 'required|date',
            'entrance_test_time'        => 'nullable|string|max:10',
            'entrance_test_venue'       => 'nullable|string|max:200',
            'entrance_test_invigilator' => 'nullable|string|max:200',
            'entrance_test_marks'       => 'nullable|numeric|min:0|max:999',
        ]);
        Enquiry::findOrFail($id)->update($request->only([
            'entrance_test_date', 'entrance_test_time', 'entrance_test_venue',
            'entrance_test_invigilator', 'entrance_test_marks',
        ]));
        return back()->with('success', 'Entrance test details saved.');
    }

    public function saveInterview(Request $request, int $id)
    {
        $request->validate([
            'interview_date'        => 'required|date',
            'interview_time'        => 'nullable|string|max:10',
            'interview_interviewer' => 'nullable|string|max:200',
            'interview_feedback'    => 'nullable|string|max:1000',
        ]);
        Enquiry::findOrFail($id)->update($request->only([
            'interview_date', 'interview_time', 'interview_interviewer', 'interview_feedback',
        ]));
        return back()->with('success', 'Interview details saved.');
    }

    public function saveDocChecklist(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);
        $docs    = $request->input('docs', []);
        $enquiry->update(['doc_checklist' => $docs]);
        return back()->with('success', 'Document checklist updated.');
    }

    public function flagMissingDocs(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);
        $missing = $request->input('missing_docs', []);
        $enquiry->update([
            'missing_docs'   => $missing,
            'docs_flag_note' => $request->docs_flag_note,
        ]);
        return back()->with('success', empty($missing) ? 'Missing document flags cleared.' : count($missing) . ' document(s) flagged as missing.');
    }

    public function entranceTestHallTicket(int $id)
    {
        $enquiry = \App\Models\Enquiry::with(['class', 'academicYear'])->findOrFail($id);

        abort_if(! $enquiry->entrance_test_date, 404, 'Entrance test not yet scheduled.');

        $school = \App\Models\SchoolSetting::first();

        $qrData = implode(' | ', array_filter([
            'ENQ: ' . $enquiry->enquiry_number,
            'Name: ' . $enquiry->student_name,
            'Test: ' . $enquiry->entrance_test_date->format('d M Y'),
            $enquiry->entrance_test_time ? 'Time: ' . $enquiry->entrance_test_time : null,
            $enquiry->entrance_test_venue ? 'Venue: ' . $enquiry->entrance_test_venue : null,
        ]));

        $qrCode = null;
        try {
            $qrCode = base64_encode(
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(120)->generate($qrData)
            );
        } catch (\Exception $e) {
            // QR optional
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.entrance-hall-ticket', compact('enquiry', 'school', 'qrCode'));
        $pdf->setPaper('A5', 'portrait');

        return $pdf->stream('entrance-hall-ticket-' . $enquiry->enquiry_number . '.pdf');
    }

    /* ------------------------------------------------------------------ */
    /*  Analytics Report Exports                                            */
    /* ------------------------------------------------------------------ */

    private function buildAnalyticsData(): array
    {
        $currentYear = AcademicYear::current();

        $bySource = Enquiry::select('source', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('source')->pluck('total', 'source');

        $byStatus = Enquiry::select('status', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('status')->pluck('total', 'status');

        $byClass = Enquiry::with('class')
            ->select('class_id', DB::raw('COUNT(*) as total'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->groupBy('class_id')->get();

        // Class-wise vs seat capacity
        $seats = SeatCapacity::with('class')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get()->groupBy('class_id');
        $classSeats = $byClass->map(function ($row) use ($seats) {
            $cap = $seats->get($row->class_id)?->sum('total_seats') ?? 0;
            return [
                'class'      => $row->class?->name ?? 'Unknown',
                'enquiries'  => $row->total,
                'seats'      => $cap,
                'fill_pct'   => $cap > 0 ? round($row->total / $cap * 100, 1) : null,
            ];
        })->sortBy('class')->values();

        $monthlyTrend = Enquiry::select(DB::raw('MONTH(created_at) as m'), DB::raw('COUNT(*) as total'))
            ->whereYear('created_at', now()->year)
            ->groupBy('m')->orderBy('m')->pluck('total', 'm');

        $byCounsellor = Enquiry::select('assigned_to', DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status IN (\'confirmed\',\'enrolled\',\'converted\') THEN 1 ELSE 0 END) as converted'))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->whereNotNull('assigned_to')
            ->with('assignedTo')
            ->groupBy('assigned_to')->get();

        $prevYear = AcademicYear::where('id', '<', $currentYear?->id)->orderByDesc('id')->first();
        $yoyData  = null;
        if ($currentYear && $prevYear) {
            $yoyData = [
                'current' => [
                    'year'      => $currentYear->name,
                    'enquiries' => Enquiry::where('academic_year_id', $currentYear->id)->count(),
                    'converted' => Enquiry::where('academic_year_id', $currentYear->id)
                        ->whereIn('status', ['confirmed', 'enrolled', 'converted'])->count(),
                ],
                'prev' => [
                    'year'      => $prevYear->name,
                    'enquiries' => Enquiry::where('academic_year_id', $prevYear->id)->count(),
                    'converted' => Enquiry::where('academic_year_id', $prevYear->id)
                        ->whereIn('status', ['confirmed', 'enrolled', 'converted'])->count(),
                ],
            ];
        }

        $school = SchoolSetting::first();

        return compact('currentYear', 'bySource', 'byStatus', 'byClass', 'classSeats',
            'monthlyTrend', 'byCounsellor', 'yoyData', 'school');
    }

    public function analyticsReportPdf()
    {
        $data = $this->buildAnalyticsData();
        $pdf  = Pdf::loadView('pdf.admission-analytics', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('admission-analytics-' . now()->format('Y-m-d') . '.pdf');
    }

    public function analyticsReportExcel()
    {
        $data = $this->buildAnalyticsData();

        // Sheet 1: Source-wise
        $sourceRows = $data['bySource']->map(fn($t, $s) => ['Source' => ucfirst($s), 'Count' => $t])->values()->toArray();
        // Sheet 2: Status-wise
        $statusRows = $data['byStatus']->map(fn($t, $s) => ['Status' => ucfirst(str_replace('_', ' ', $s)), 'Count' => $t])->values()->toArray();
        // Sheet 3: Class vs seats
        $seatRows = $data['classSeats']->map(fn($r) => [
            'Class'     => $r['class'],
            'Enquiries' => $r['enquiries'],
            'Seats'     => $r['seats'],
            'Fill %'    => $r['fill_pct'] !== null ? $r['fill_pct'] . '%' : 'N/A',
        ])->toArray();
        // Sheet 4: Monthly trend
        $months = ['', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $trendRows = $data['monthlyTrend']->map(fn($t, $m) => ['Month' => $months[$m] ?? $m, 'Enquiries' => $t])->values()->toArray();
        // Sheet 5: Counsellor
        $counsellorRows = $data['byCounsellor']->map(fn($r) => [
            'Counsellor' => $r->assignedTo?->name ?? ('Staff #' . $r->assigned_to),
            'Total'      => $r->total,
            'Converted'  => $r->converted,
            'Rate %'     => $r->total > 0 ? round($r->converted / $r->total * 100, 1) . '%' : '0%',
        ])->toArray();
        // Sheet 6: YoY
        $yoyRows = [];
        if ($data['yoyData']) {
            $yoyRows = [
                ['Year' => $data['yoyData']['prev']['year'],    'Enquiries' => $data['yoyData']['prev']['enquiries'],    'Converted' => $data['yoyData']['prev']['converted']],
                ['Year' => $data['yoyData']['current']['year'], 'Enquiries' => $data['yoyData']['current']['enquiries'], 'Converted' => $data['yoyData']['current']['converted']],
            ];
        }

        // Combine into single sheet with sections
        $rows = [];
        $rows[] = ['=== SOURCE-WISE BREAKDOWN ===', '', '', ''];
        foreach ($sourceRows as $r) $rows[] = [$r['Source'], $r['Count'], '', ''];
        $rows[] = ['', '', '', ''];
        $rows[] = ['=== STATUS-WISE BREAKDOWN ===', '', '', ''];
        foreach ($statusRows as $r) $rows[] = [$r['Status'], $r['Count'], '', ''];
        $rows[] = ['', '', '', ''];
        $rows[] = ['=== CLASS vs SEAT CAPACITY ===', '', '', ''];
        $rows[] = ['Class', 'Enquiries', 'Seats', 'Fill %'];
        foreach ($seatRows as $r) $rows[] = array_values($r);
        $rows[] = ['', '', '', ''];
        $rows[] = ['=== MONTHLY TREND ===', '', '', ''];
        foreach ($trendRows as $r) $rows[] = [$r['Month'], $r['Enquiries'], '', ''];
        $rows[] = ['', '', '', ''];
        $rows[] = ['=== COUNSELLOR CONVERSION ===', '', '', ''];
        $rows[] = ['Counsellor', 'Total', 'Converted', 'Rate %'];
        foreach ($counsellorRows as $r) $rows[] = array_values($r);
        if ($yoyRows) {
            $rows[] = ['', '', '', ''];
            $rows[] = ['=== YEAR-OVER-YEAR ===', '', '', ''];
            $rows[] = ['Year', 'Enquiries', 'Converted', ''];
            foreach ($yoyRows as $r) $rows[] = [$r['Year'], $r['Enquiries'], $r['Converted'], ''];
        }

        $export = new ArrayExport($rows, ['Category / Item', 'Value', 'Value 2', 'Value 3']);
        return Excel::download($export, 'admission-analytics-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ── Application Form Builder ──────────────────────────

    public function formBuilder(Request $request)
    {
        $years   = AcademicYear::orderByDesc('start_date')->get();
        $classes = Classes::active()->get();
        $configs = \App\Models\ApplicationFormConfig::with(['academicYear', 'class'])
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->get();
        return view('admissions.form-builder', compact('years', 'classes', 'configs'));
    }

    public function storeFormConfig(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:200',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $fields = [];
        foreach (['student_name','dob','gender','nationality','religion','caste','category',
                  'parent_name','parent_mobile','parent_email','address','previous_school',
                  'siblings_in_school','annual_income'] as $f) {
            if ($request->boolean('field_' . $f)) {
                $fields[] = [
                    'name'     => $f,
                    'label'    => ucwords(str_replace('_', ' ', $f)),
                    'required' => $request->boolean('req_' . $f),
                    'type'     => in_array($f, ['dob']) ? 'date' : (in_array($f,['gender','category','religion','nationality']) ? 'select' : 'text'),
                ];
            }
        }

        $docFields = [];
        foreach (['birth_certificate','aadhaar_card','transfer_certificate','passport_photo',
                  'caste_certificate','address_proof','income_certificate'] as $d) {
            if ($request->boolean('doc_' . $d)) {
                $docFields[] = [
                    'name'     => $d,
                    'label'    => ucwords(str_replace('_', ' ', $d)),
                    'required' => $request->boolean('docreq_' . $d),
                    'types'    => ['pdf', 'jpg', 'jpeg', 'png'],
                    'max_mb'   => 5,
                ];
            }
        }

        \App\Models\ApplicationFormConfig::create([
            'title'            => $request->title,
            'description'      => $request->description,
            'academic_year_id' => $request->academic_year_id,
            'class_id'         => $request->class_id ?: null,
            'fields'           => $fields,
            'document_fields'  => $docFields,
            'application_fee'  => $request->application_fee ?? 0,
            'open_from'        => $request->open_from ?: null,
            'open_until'       => $request->open_until ?: null,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Application form created successfully.');
    }

    public function toggleFormConfig(int $id)
    {
        $config = \App\Models\ApplicationFormConfig::findOrFail($id);
        $config->update(['is_active' => !$config->is_active]);
        return back()->with('success', 'Form ' . ($config->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function deleteFormConfig(int $id)
    {
        \App\Models\ApplicationFormConfig::findOrFail($id)->delete();
        return back()->with('success', 'Form configuration deleted.');
    }

    public function applications(Request $request)
    {
        $configs = \App\Models\ApplicationFormConfig::orderByDesc('created_at')->get();
        $apps    = \App\Models\StudentApplication::with(['formConfig.class', 'formConfig.academicYear'])
            ->when($request->form_config_id, fn($q, $v) => $q->where('form_config_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q2) use ($v) {
                $q2->where('student_name', 'like', "%$v%")
                   ->orWhere('application_number', 'like', "%$v%")
                   ->orWhere('parent_mobile', 'like', "%$v%");
            }))
            ->latest()
            ->paginate(20)->withQueryString();
        return view('admissions.applications', compact('apps', 'configs'));
    }

    public function updateApplicationStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:submitted,under_review,shortlisted,rejected,admitted']);
        \App\Models\StudentApplication::findOrFail($id)->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => Auth::id(),
        ]);
        return back()->with('success', 'Application status updated.');
    }

    public function applicationPdf(int $id)
    {
        $app    = \App\Models\StudentApplication::with('formConfig')->findOrFail($id);
        $school = SchoolSetting::first();
        $pdf    = Pdf::loadView('pdf.application-form', compact('app', 'school'));
        return $pdf->setPaper('A4', 'portrait')->download('application-' . $app->application_number . '.pdf');
    }

    // ── Public Application Form (unauthenticated) ─────────

    public function publicApplicationForm(string $token)
    {
        $config = \App\Models\ApplicationFormConfig::with(['academicYear', 'class'])
            ->where('link_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        if ($config->open_until && now()->gt($config->open_until)) {
            abort(410, 'This application form has closed.');
        }
        if ($config->open_from && now()->lt($config->open_from)) {
            abort(403, 'This application form is not yet open.');
        }

        return view('admissions.public-application', compact('config'));
    }

    public function submitPublicApplication(Request $request, string $token)
    {
        $config = \App\Models\ApplicationFormConfig::where('link_token', $token)->where('is_active', true)->firstOrFail();

        // Validate required fields from config
        $rules = ['parent_mobile' => 'required|string|max:20'];
        foreach (($config->fields ?? []) as $field) {
            if ($field['required'] ?? false) {
                $rules['fields.' . $field['name']] = 'required|string|max:500';
            }
        }
        $request->validate($rules);

        // Handle document uploads
        $documents = [];
        foreach (($config->document_fields ?? []) as $docField) {
            if ($request->hasFile('docs.' . $docField['name'])) {
                $path = $request->file('docs.' . $docField['name'])->store('applications/docs', 'public');
                $documents[$docField['name']] = $path;
            }
        }

        // Generate application number
        $yearPrefix = $config->academicYear?->start_date
            ? \Carbon\Carbon::parse($config->academicYear->start_date)->format('Y')
            : date('Y');
        $count  = \App\Models\StudentApplication::where('form_config_id', $config->id)->count();
        $appNum = 'APP-' . $yearPrefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $formData = $request->input('fields', []);
        $appModel = \App\Models\StudentApplication::create([
            'application_number' => $appNum,
            'form_config_id'     => $config->id,
            'academic_year_id'   => $config->academic_year_id,
            'class_id'           => $config->class_id,
            'form_data'          => $formData,
            'documents'          => $documents,
            'student_name'       => $formData['student_name'] ?? null,
            'parent_name'        => $formData['parent_name'] ?? null,
            'parent_mobile'      => $request->parent_mobile,
            'parent_email'       => $request->input('fields.parent_email'),
            'status'             => 'submitted',
        ]);

        return redirect()->route('apply.success', $appModel->application_number);
    }

    public function applicationSuccess(string $number)
    {
        $app = \App\Models\StudentApplication::with('formConfig')->where('application_number', $number)->firstOrFail();
        return view('admissions.application-success', compact('app'));
    }

    /**
     * One-click Enquiry to Admission conversion initiation
     */
    public function convertEnquiryToAdmission(int $id)
    {
        $enquiry = Enquiry::with('convertedStudent')->findOrFail($id);

        if ($enquiry->converted_student_id || $enquiry->status === 'converted') {
            $student = $enquiry->convertedStudent;
            if ($student) {
                return redirect()->route('students.show', $student->id)
                    ->with('info', "Enquiry #{$enquiry->enquiry_number} was already converted into Student {$student->first_name} {$student->last_name} ({$student->admission_no}).");
            }
        }

        return redirect()->route('admissions.create', ['enquiry_id' => $enquiry->id]);
    }

    /**
     * Add a structured follow-up record to an enquiry
     */
    public function addEnquiryFollowUp(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);

        $validated = $request->validate([
            'contact_date'        => 'required|date',
            'contact_person'      => 'nullable|string|max:150',
            'contact_method'      => 'required|string|in:phone,call,whatsapp,email,in_person,visit,other',
            'discussion'          => 'required|string|max:2000',
            'parent_response'     => 'nullable|string|max:100',
            'next_follow_up_date' => 'nullable|date',
            'new_status'          => 'nullable|string|max:50',
            'assigned_staff_id'   => 'nullable|exists:users,id',
        ]);

        $prevStatus = $enquiry->status;
        $newStatus  = $validated['new_status'] ?? $prevStatus;

        // Save follow-up
        $followUp = EnquiryFollowUp::create([
            'enquiry_id'          => $enquiry->id,
            'follow_up_date'      => $validated['contact_date'],
            'contact_person'      => $validated['contact_person'] ?? ($enquiry->father_name ?: $enquiry->parent_name),
            'contact_method'      => $validated['contact_method'],
            'discussion'          => $validated['discussion'],
            'parent_response'     => $validated['parent_response'] ?? null,
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'notes'               => $validated['discussion'],
            'status'              => $newStatus,
            'created_by'          => Auth::id(),
            'assigned_staff_id'   => $validated['assigned_staff_id'] ?? Auth::id(),
        ]);

        // Update enquiry
        $updates = [];
        if (!empty($validated['next_follow_up_date'])) {
            $updates['follow_up_date'] = $validated['next_follow_up_date'];
        }
        if ($newStatus && $newStatus !== $prevStatus) {
            $updates['status'] = $newStatus;
            EnquiryStatusHistory::create([
                'enquiry_id'      => $enquiry->id,
                'previous_status' => $prevStatus,
                'new_status'      => $newStatus,
                'reason'          => 'Follow-up interaction',
                'remarks'         => $validated['discussion'],
                'changed_by'      => Auth::id(),
            ]);
        }
        if (!empty($updates)) {
            $enquiry->update($updates);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Follow-up recorded successfully.',
                'follow_up' => $followUp,
            ]);
        }

        return back()->with('success', 'Follow-up recorded successfully.');
    }

    /**
     * Update Enquiry Status with optional closure reason
     */
    public function updateEnquiryStatusAction(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);

        $validated = $request->validate([
            'status'                 => 'required|string',
            'non_conversion_reason'  => 'nullable|string|max:150',
            'non_conversion_remarks' => 'nullable|string|max:1000',
        ]);

        $prevStatus = $enquiry->status;
        $newStatus  = $validated['status'];

        $updates = ['status' => $newStatus];

        if (in_array($newStatus, ['not_interested', 'lost', 'on_hold'])) {
            $updates['non_conversion_reason']  = $validated['non_conversion_reason'] ?? null;
            $updates['non_conversion_remarks'] = $validated['non_conversion_remarks'] ?? null;
            $updates['closed_by']              = Auth::id();
            $updates['closed_at']              = now();
        }

        $enquiry->update($updates);

        // Record history
        EnquiryStatusHistory::create([
            'enquiry_id'      => $enquiry->id,
            'previous_status' => $prevStatus,
            'new_status'      => $newStatus,
            'reason'          => $validated['non_conversion_reason'] ?? 'Status updated by staff',
            'remarks'         => $validated['non_conversion_remarks'] ?? null,
            'changed_by'      => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Enquiry status updated successfully.',
            ]);
        }

        return back()->with('success', 'Enquiry status updated successfully.');
    }

    /**
     * Reopen a closed or lost enquiry
     */
    public function reopenEnquiry(Request $request, int $id)
    {
        $enquiry = Enquiry::findOrFail($id);
        $prevStatus = $enquiry->status;

        $enquiry->update([
            'status'                 => 'follow_up',
            'non_conversion_reason'  => null,
            'non_conversion_remarks' => null,
            'closed_by'              => null,
            'closed_at'              => null,
        ]);

        EnquiryStatusHistory::create([
            'enquiry_id'      => $enquiry->id,
            'previous_status' => $prevStatus,
            'new_status'      => 'follow_up',
            'reason'          => 'Enquiry Reopened',
            'remarks'         => $request->input('remarks', 'Reopened for active follow-up.'),
            'changed_by'      => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Enquiry reopened successfully.',
            ]);
        }

        return back()->with('success', 'Enquiry reopened successfully.');
    }

    /**
     * Fetch complete Enquiry details, timeline and history for modal/drawer (AJAX)
     */
    public function enquiryDetailsJson(int $id)
    {
        $enquiry = Enquiry::with([
            'class',
            'academicYear',
            'followUps.creator',
            'statusHistory.user',
            'convertedStudent.currentEnrollment.section',
            'assignedTo',
            'closedBy',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'enquiry' => [
                'id'                     => $enquiry->id,
                'enquiry_number'         => $enquiry->enquiry_number,
                'student_name'           => $enquiry->student_name,
                'dob'                    => $enquiry->dob ? $enquiry->dob->format('d M Y') : '—',
                'gender'                 => ucfirst($enquiry->gender ?? 'male'),
                'class_name'             => $enquiry->class?->name ?? '—',
                'academic_year'          => $enquiry->academicYear?->name ?? '2026–2027',
                'parent_name'            => $enquiry->father_name ?: ($enquiry->parent_name ?: '—'),
                'parent_mobile'          => $enquiry->father_mobile ?: ($enquiry->parent_mobile ?: '—'),
                'parent_email'           => $enquiry->email ?: '—',
                'address'                => $enquiry->address ?: '—',
                'source'                 => $enquiry->source ?? 'Walk-in',
                'status'                 => $enquiry->status,
                'priority'               => $enquiry->priority ?? 'Warm',
                'follow_up_date'         => $enquiry->follow_up_date ? $enquiry->follow_up_date->format('d M Y') : null,
                'non_conversion_reason'  => $enquiry->non_conversion_reason,
                'non_conversion_remarks' => $enquiry->non_conversion_remarks,
                'converted_student_id'   => $enquiry->converted_student_id,
                'converted_student_name' => $enquiry->convertedStudent?->full_name,
                'converted_student_adm'  => $enquiry->convertedStudent?->admission_no,
                'converted_at'           => $enquiry->converted_at?->format('d M Y, h:i A'),
                'closed_at'              => $enquiry->closed_at?->format('d M Y, h:i A'),
                'closed_by_name'         => $enquiry->closedBy?->name,
                'created_at'             => $enquiry->created_at->format('d M Y, h:i A'),
            ],
            'follow_ups' => $enquiry->followUps->map(fn($f) => [
                'id'                  => $f->id,
                'date'                => $f->follow_up_date ? $f->follow_up_date->format('d M Y') : $f->created_at->format('d M Y'),
                'contact_method'      => ucfirst(str_replace('_', ' ', $f->contact_method ?? 'phone')),
                'contact_person'      => $f->contact_person,
                'discussion'          => $f->discussion ?: ($f->notes ?: '—'),
                'parent_response'     => $f->parent_response,
                'next_date'           => $f->next_follow_up_date ? \Carbon\Carbon::parse($f->next_follow_up_date)->format('d M Y') : null,
                'staff_name'          => $f->creator?->name ?? 'Staff',
            ]),
            'status_history' => $enquiry->statusHistory->map(fn($h) => [
                'id'              => $h->id,
                'previous_status' => ucfirst(str_replace('_', ' ', $h->previous_status ?? '—')),
                'new_status'      => ucfirst(str_replace('_', ' ', $h->new_status)),
                'reason'          => $h->reason,
                'remarks'         => $h->remarks,
                'changed_by'      => $h->user?->name ?? 'System',
                'date'            => $h->created_at->format('d M Y, h:i A'),
            ]),
        ]);
    }
}
