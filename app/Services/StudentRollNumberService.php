<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentRollNumberService
{
    /**
     * Roman numeral to numeric mapping.
     */
    protected static array $romanMap = [
        'I' => '1', 'II' => '2', 'III' => '3', 'IV' => '4', 'V' => '5',
        'VI' => '6', 'VII' => '7', 'VIII' => '8', 'IX' => '9', 'X' => '10',
        'XI' => '11', 'XII' => '12',
    ];

    /**
     * Compute clean class code for roll numbering.
     * Numeric classes (I to XII) return numeric value ('1' to '12').
     * Kindergarten/Early years return 'PKG', 'LKG', 'UKG'.
     */
    public static function getClassCode(?Classes $class, string $prefixStyle = 'standard'): string
    {
        if (!$class) {
            return '1';
        }

        $name = trim($class->name);
        $upperName = strtoupper($name);

        // Roman style requested explicitly
        if ($prefixStyle === 'roman') {
            // If name is already roman or starts with Roman numerals
            $cleanRoman = preg_replace('/[^IVXLCDM]/i', '', $upperName);
            if (!empty($cleanRoman)) {
                return $cleanRoman;
            }
        }

        // Standard style: use numeric value if present
        if (!empty($class->numeric_value) && (int)$class->numeric_value > 0) {
            return (string)(int)$class->numeric_value;
        }

        // Check roman numerals map
        if (isset(self::$romanMap[$upperName])) {
            return self::$romanMap[$upperName];
        }

        // Check Kindergarten wings
        if (preg_match('/pre/i', $name)) {
            return 'PKG';
        }
        if (preg_match('/lkg/i', $name)) {
            return 'LKG';
        }
        if (preg_match('/ukg/i', $name)) {
            return 'UKG';
        }

        // Check if string contains digits (e.g. "Class 6" or "Std 10")
        if (preg_match('/\d+/', $name, $matches)) {
            return $matches[0];
        }

        // Fallback: up to 3 uppercase alphanumeric characters
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3)) ?: '1';
    }

    /**
     * Compute clean section code (e.g. 'A', 'B', 'C').
     */
    public static function getSectionCode(mixed $section): string
    {
        if (!$section) {
            return 'A';
        }

        $name = is_string($section) ? $section : ($section->name ?? 'A');
        $clean = trim(preg_replace('/^section\s*/i', '', $name));
        $clean = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $clean), 0, 2));

        return $clean ?: 'A';
    }

    /**
     * Format a sequential roll number.
     * E.g. '6A001', '6B002', 'LKGC015'.
     */
    public static function formatRollNumber(
        string $classCode,
        string $sectionCode,
        int $sequence,
        int $padLength = 3,
        ?string $customPrefix = null
    ): string {
        $prefix = $customPrefix !== null && trim($customPrefix) !== ''
            ? strtoupper(trim($customPrefix))
            : ($classCode . $sectionCode);

        return $prefix . str_pad((string)$sequence, $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Consistent deterministic sort comparator for student enrollments.
     * Defined Rules:
     * 1. Normalized case-insensitive full name (A to Z)
     * 2. If identical full names: Date of Birth (older student first)
     * 3. If identical DOB: Permanent admission number (natural ascending)
     * 4. Student ID ascending
     */
    public static function compareEnrollments(StudentEnrollment $a, StudentEnrollment $b): int
    {
        $stA = $a->student;
        $stB = $b->student;

        // 1. Normalized full name (lowercase, multiple spaces collapsed, trimmed)
        $fullNameA = mb_strtolower(trim(preg_replace('/\s+/', ' ', ($stA?->first_name ?? '') . ' ' . ($stA?->middle_name ?? '') . ' ' . ($stA?->last_name ?? ''))));
        $fullNameB = mb_strtolower(trim(preg_replace('/\s+/', ' ', ($stB?->first_name ?? '') . ' ' . ($stB?->middle_name ?? '') . ' ' . ($stB?->last_name ?? ''))));

        $nameCmp = strcmp($fullNameA, $fullNameB);
        if ($nameCmp !== 0) {
            return $nameCmp;
        }

        // 2. Secondary rule for identical names: Date of birth (ascending - older first)
        $dobA = $stA?->dob ? $stA->dob->format('Y-m-d') : '9999-99-99';
        $dobB = $stB?->dob ? $stB->dob->format('Y-m-d') : '9999-99-99';
        $dobCmp = strcmp($dobA, $dobB);
        if ($dobCmp !== 0) {
            return $dobCmp;
        }

        // 3. Tertiary rule for identical names & dob: Permanent admission number (natural sort)
        $admA = (string)($stA?->admission_no ?? '');
        $admB = (string)($stB?->admission_no ?? '');
        $admCmp = strnatcasecmp($admA, $admB);
        if ($admCmp !== 0) {
            return $admCmp;
        }

        // 4. Quaternary rule: Student ID
        return ($stA?->id ?? 0) <=> ($stB?->id ?? 0);
    }

    /**
     * Preview roll number regeneration without writing to database.
     * Separately sorts each section independently (e.g. 6-A, 6-B, 6-C each have their own 001..N).
     * Permanent admission numbers are untouched.
     */
    public function previewRegeneration(
        int $classId,
        ?int $sectionId = null,
        ?int $academicYearId = null,
        string $prefixStyle = 'standard',
        int $startSeq = 1,
        ?string $customPrefix = null
    ): array {
        $academicYear = $academicYearId ? AcademicYear::find($academicYearId) : null;
        if (!$academicYear) {
            $curr = AcademicYear::current();
            $hasEnr = $curr ? StudentEnrollment::where('class_id', $classId)->where('academic_year_id', $curr->id)->where('status', 'active')->exists() : false;
            if ($hasEnr) {
                $academicYear = $curr;
            } else {
                $latestYearId = StudentEnrollment::where('class_id', $classId)
                    ->where('status', 'active')
                    ->latest('academic_year_id')
                    ->value('academic_year_id');
                $academicYear = $latestYearId ? AcademicYear::find($latestYearId) : $curr;
            }
        }
        $classModel = Classes::findOrFail($classId);

        $query = StudentEnrollment::with(['student', 'section', 'class'])
            ->whereHas('student')
            ->where('class_id', $classId)
            ->where('status', 'active');

        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $allEnrollments = $query->get();

        // Group by section so Class 6-A, 6-B, 6-C are processed completely independently
        $grouped = $allEnrollments->groupBy(function ($enr) {
            return $enr->section_id ?: 0;
        });

        $sectionsPreview = [];
        $totalChanged = 0;
        $totalUnchanged = 0;
        $totalStudents = 0;

        $classCode = self::getClassCode($classModel, $prefixStyle);

        foreach ($grouped as $secId => $enrollments) {
            $sectionModel = $secId ? Section::find($secId) : null;
            $secName = $sectionModel ? $sectionModel->name : 'Unassigned';
            $sectionCode = self::getSectionCode($sectionModel);

            // Sort using our consistent, deterministic comparator
            $sortedEnrollments = $enrollments->sort([self::class, 'compareEnrollments'])->values();

            $studentItems = [];
            $secChanged = 0;
            $secUnchanged = 0;

            $seq = max(1, $startSeq);
            foreach ($sortedEnrollments as $enr) {
                $student = $enr->student;
                $currentRoll = $enr->roll_number ?: ($student?->roll_number ?: '—');
                
                // Form proposed roll number
                $newRoll = self::formatRollNumber($classCode, $sectionCode, $seq, 3, $customPrefix);

                $isChanged = ($currentRoll !== $newRoll);
                if ($isChanged) {
                    $secChanged++;
                } else {
                    $secUnchanged++;
                }

                $studentItems[] = [
                    'enrollment_id'   => $enr->id,
                    'student_id'      => $student?->id,
                    'full_name'       => $student?->full_name ?? '—',
                    'first_name'      => $student?->first_name ?? '',
                    'last_name'       => $student?->last_name ?? '',
                    'admission_no'    => $student?->admission_no ?? '—', // Permanent & protected
                    'dob'             => $student?->dob?->format('d M Y') ?? '—',
                    'gender'          => ucfirst($student?->gender ?? '—'),
                    'photo'           => $student?->photo,
                    'current_roll'    => $currentRoll,
                    'proposed_roll'   => $newRoll,
                    'sequence'        => $seq,
                    'is_changed'      => $isChanged,
                ];

                $seq++;
            }

            $totalChanged += $secChanged;
            $totalUnchanged += $secUnchanged;
            $totalStudents += count($studentItems);

            $sectionsPreview[] = [
                'section_id'      => $secId,
                'section_name'    => $secName,
                'section_code'    => $sectionCode,
                'class_code'      => $classCode,
                'student_count'   => count($studentItems),
                'changed_count'   => $secChanged,
                'unchanged_count' => $secUnchanged,
                'students'        => $studentItems,
            ];
        }

        // Sort sections by name (A, B, C...)
        usort($sectionsPreview, fn($a, $b) => strcmp($a['section_name'], $b['section_name']));

        return [
            'academic_year'   => $academicYear?->name ?? 'Current Academic Year',
            'academic_year_id'=> $academicYear?->id,
            'class_id'        => $classModel->id,
            'class_name'      => $classModel->name,
            'class_code'      => $classCode,
            'section_id'      => $sectionId,
            'prefix_style'    => $prefixStyle,
            'start_seq'       => $startSeq,
            'total_students'  => $totalStudents,
            'total_changed'   => $totalChanged,
            'total_unchanged' => $totalUnchanged,
            'sections'        => $sectionsPreview,
        ];
    }

    /**
     * Authorized execution of roll number regeneration with confirmation.
     * Updates StudentEnrollment::roll_number and Student::roll_number.
     * NEVER modifies Student::admission_no.
     */
    public function applyRegeneration(
        int $classId,
        ?int $sectionId = null,
        ?int $academicYearId = null,
        string $prefixStyle = 'standard',
        int $startSeq = 1,
        ?string $customPrefix = null,
        ?int $authorizedUserId = null
    ): array {
        $preview = $this->previewRegeneration(
            $classId,
            $sectionId,
            $academicYearId,
            $prefixStyle,
            $startSeq,
            $customPrefix
        );

        $affectedCount = 0;
        $classModel = Classes::findOrFail($classId);

        DB::transaction(function () use ($preview, &$affectedCount, $classModel, $authorizedUserId) {
            foreach ($preview['sections'] as $sec) {
                foreach ($sec['students'] as $item) {
                    if (!$item['enrollment_id']) {
                        continue;
                    }

                    $enrollment = StudentEnrollment::find($item['enrollment_id']);
                    if (!$enrollment) {
                        continue;
                    }

                    $oldRoll = $enrollment->roll_number;
                    $newRoll = $item['proposed_roll'];

                    // Update enrollment roll number
                    $enrollment->roll_number = $newRoll;
                    $enrollment->save();

                    // If student exists and this enrollment is active, keep student->roll_number synced
                    if ($item['student_id']) {
                        $student = Student::find($item['student_id']);
                        if ($student) {
                            $student->roll_number = $newRoll;
                            // Explicitly protect permanent admission_no: NEVER change it
                            $student->save();
                        }
                    }

                    $affectedCount++;
                }
            }

            // Record immutable audit log
            try {
                AuditLog::record(
                    'roll_numbers_regenerated',
                    $classModel,
                    ['class' => $classModel->name, 'section_id' => $preview['section_id']],
                    [
                        'total_students'   => $preview['total_students'],
                        'total_changed'    => $preview['total_changed'],
                        'prefix_style'     => $preview['prefix_style'],
                        'authorized_by'    => $authorizedUserId ?: Auth::id(),
                    ],
                    'student_management'
                );
            } catch (\Throwable $e) {
                Log::warning('AuditLog recording failed: ' . $e->getMessage());
            }
        });

        return [
            'status'         => 'success',
            'affected_count' => $affectedCount,
            'total_students' => $preview['total_students'],
            'class_name'     => $preview['class_name'],
            'section_count'  => count($preview['sections']),
        ];
    }

    /**
     * Allocate sections to students (e.g. Balanced or Alphabetical split)
     * and optionally generate fresh sequential roll numbers per section.
     */
    public function allocateSections(
        int $classId,
        ?int $academicYearId = null,
        string $strategy = 'balance',
        array $sectionIds = [],
        bool $generateRolls = true
    ): array {
        $academicYear = $academicYearId ? AcademicYear::find($academicYearId) : AcademicYear::current();
        $classModel = Classes::findOrFail($classId);

        if (empty($sectionIds)) {
            $sectionIds = Section::where('class_id', $classId)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('id')
                ->toArray();
        }

        if (empty($sectionIds)) {
            throw new \InvalidArgumentException('No active sections available for this class.');
        }

        $enrollments = StudentEnrollment::with('student')
            ->whereHas('student')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->when($academicYear, fn($q) => $q->where('academic_year_id', $academicYear->id))
            ->get();

        if ($enrollments->isEmpty()) {
            throw new \RuntimeException('No active students found in this class.');
        }

        // Sort all students alphabetically using defined consistent rule
        $sorted = $enrollments->sort([self::class, 'compareEnrollments'])->values();
        $totalStudents = $sorted->count();
        $secCount = count($sectionIds);

        DB::transaction(function () use ($sorted, $sectionIds, $strategy, $totalStudents, $secCount) {
            if ($strategy === 'alphabetical_split') {
                // Split alphabet chunks into sections (e.g. 3 sections = 3 equal chunks)
                $chunkSize = (int)ceil($totalStudents / $secCount);
                $chunks = $sorted->chunk($chunkSize);

                $secIndex = 0;
                foreach ($chunks as $chunk) {
                    $targetSecId = $sectionIds[$secIndex % $secCount];
                    foreach ($chunk as $enr) {
                        $enr->section_id = $targetSecId;
                        $enr->save();
                    }
                    $secIndex++;
                }
            } else {
                // Balanced / Round-Robin: A, B, C, A, B, C...
                foreach ($sorted as $idx => $enr) {
                    $targetSecId = $sectionIds[$idx % $secCount];
                    $enr->section_id = $targetSecId;
                    $enr->save();
                }
            }
        });

        // If requested, generate fresh alphabetical roll numbers independently for each section
        if ($generateRolls) {
            $this->applyRegeneration($classId, null, $academicYear?->id);
        }

        return [
            'status'         => 'success',
            'allocated_count'=> $totalStudents,
            'sections_used'  => $secCount,
        ];
    }

    /**
     * Academic year promotion independent generation:
     * Generates class and section lists for the next academic year independently.
     */
    public function generateNextYearRollNumbers(
        int $fromClassId,
        int $toClassId,
        int $fromYearId,
        int $toYearId,
        array $studentIds = [],
        bool $retainSections = true,
        array $customSectionMap = []
    ): array {
        $fromYear = AcademicYear::findOrFail($fromYearId);
        $toYear   = AcademicYear::findOrFail($toYearId);
        $toClass  = Classes::findOrFail($toClassId);

        // Fetch source enrollments
        $query = StudentEnrollment::with(['student', 'section'])
            ->whereHas('student')
            ->where('class_id', $fromClassId)
            ->where('academic_year_id', $fromYearId)
            ->where('status', 'active');

        if (!empty($studentIds)) {
            $query->whereIn('student_id', $studentIds);
        }

        $sourceEnrollments = $query->get();
        if ($sourceEnrollments->isEmpty()) {
            throw new \RuntimeException('No eligible students found in the source class for promotion.');
        }

        // Available target sections for the toClass
        $targetSections = Section::where('class_id', $toClassId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($targetSections->isEmpty()) {
            // Auto-create Section A if none exist
            $defaultSec = Section::firstOrCreate(
                ['class_id' => $toClassId, 'name' => 'A'],
                ['is_active' => true]
            );
            $targetSections = collect([$defaultSec]);
        }

        $promotedCount = 0;

        DB::transaction(function () use (
            $sourceEnrollments,
            $targetSections,
            $fromClassId,
            $toClassId,
            $fromYearId,
            $toYearId,
            $retainSections,
            $customSectionMap,
            &$promotedCount
        ) {
            foreach ($sourceEnrollments as $srcEnr) {
                // Determine target section
                $targetSecId = null;

                if (!empty($customSectionMap[$srcEnr->student_id])) {
                    $targetSecId = $customSectionMap[$srcEnr->student_id];
                } elseif ($retainSections && $srcEnr->section) {
                    // Try to match same section name in target class (e.g. 'A' -> 'A')
                    $matched = $targetSections->first(function ($ts) use ($srcEnr) {
                        return strtoupper(trim(preg_replace('/^section\s*/i', '', $ts->name))) ===
                               strtoupper(trim(preg_replace('/^section\s*/i', '', $srcEnr->section->name)));
                    });
                    $targetSecId = $matched ? $matched->id : $targetSections->first()->id;
                } else {
                    $targetSecId = $targetSections->first()->id;
                }

                // Check or create enrollment in target academic year
                $targetEnrollment = StudentEnrollment::firstOrNew([
                    'student_id'       => $srcEnr->student_id,
                    'academic_year_id' => $toYearId,
                ]);

                $targetEnrollment->class_id   = $toClassId;
                $targetEnrollment->section_id = $targetSecId;
                $targetEnrollment->status     = 'active';
                $targetEnrollment->house      = $srcEnr->house;
                $targetEnrollment->save();

                // Record promotion log
                StudentPromotion::updateOrCreate(
                    [
                        'student_id'            => $srcEnr->student_id,
                        'from_academic_year_id' => $fromYearId,
                        'to_academic_year_id'   => $toYearId,
                    ],
                    [
                        'from_class_id'         => $fromClassId,
                        'to_class_id'           => $toClassId,
                        'promoted_by'           => Auth::id(),
                        'promoted_at'           => now(),
                        'status'                => 'promoted',
                    ]
                );

                $promotedCount++;
            }
        });

        // Now independently generate alphabetical roll numbers for the target academic year!
        $this->applyRegeneration($toClassId, null, $toYearId);

        return [
            'status'         => 'success',
            'promoted_count' => $promotedCount,
            'target_class'   => $toClass->name,
            'target_year'    => $toYear->name,
        ];
    }
}
