<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\IdCard;
use App\Models\IdCardAuditLog;
use App\Models\IdCardTemplate;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IdCardService
{
    /**
     * Ensure default templates exist for Student, Staff, and Visitor cards.
     */
    public function ensureDefaultTemplates(): void
    {
        $school = SchoolSetting::first();
        if ($school) {
            // Update school details if default/empty
            $needsUpdate = false;
            if ($school->school_name !== 'Erode Public School') {
                $school->school_name = 'Erode Public School';
                $needsUpdate = true;
            }
            if (empty($school->affiliation_no) || $school->affiliation_no === 'SCH001') {
                $school->affiliation_no = '1931585';
                $needsUpdate = true;
            }
            if ($school->phone !== '93677 54654') {
                $school->phone = '93677 54654';
                $needsUpdate = true;
            }
            if (str_contains($school->address ?? '', '123, Main Street') || empty($school->address)) {
                $school->address = '420, Vakkil Thottam, Manickam Palayam';
                $school->city = 'Erode';
                $school->state = 'Tamil Nadu';
                $school->pincode = '638004';
                $needsUpdate = true;
            }
            if ($needsUpdate) {
                $school->saveQuietly();
            }
        }

        $templates = [
            [
                'template_name'   => 'International Standard - Student Landscape',
                'card_type'       => IdCard::TYPE_STUDENT,
                'orientation'     => 'landscape',
                'physical_width'  => 85.60,
                'physical_height' => 53.98,
                'is_active'       => true,
                'is_default'      => true,
                'configuration_json' => IdCardTemplate::getDefaultConfig('student', 'landscape'),
            ],
            [
                'template_name'   => 'International Standard - Student Portrait',
                'card_type'       => IdCard::TYPE_STUDENT,
                'orientation'     => 'portrait',
                'physical_width'  => 53.98,
                'physical_height' => 85.60,
                'is_active'       => true,
                'is_default'      => false,
                'configuration_json' => IdCardTemplate::getDefaultConfig('student', 'portrait'),
            ],
            [
                'template_name'   => 'Executive Crest - Staff Landscape',
                'card_type'       => IdCard::TYPE_STAFF,
                'orientation'     => 'landscape',
                'physical_width'  => 85.60,
                'physical_height' => 53.98,
                'is_active'       => true,
                'is_default'      => true,
                'configuration_json' => IdCardTemplate::getDefaultConfig('staff', 'landscape'),
            ],
            [
                'template_name'   => 'Executive Crest - Staff Portrait',
                'card_type'       => IdCard::TYPE_STAFF,
                'orientation'     => 'portrait',
                'physical_width'  => 53.98,
                'physical_height' => 85.60,
                'is_active'       => true,
                'is_default'      => false,
                'configuration_json' => IdCardTemplate::getDefaultConfig('staff', 'portrait'),
            ],
            [
                'template_name'   => 'Security Access - Visitor Pass Landscape',
                'card_type'       => IdCard::TYPE_VISITOR,
                'orientation'     => 'landscape',
                'physical_width'  => 85.60,
                'physical_height' => 53.98,
                'is_active'       => true,
                'is_default'      => true,
                'configuration_json' => IdCardTemplate::getDefaultConfig('visitor', 'landscape'),
            ],
            [
                'template_name'   => 'Security Access - Visitor Pass Portrait',
                'card_type'       => IdCard::TYPE_VISITOR,
                'orientation'     => 'portrait',
                'physical_width'  => 53.98,
                'physical_height' => 85.60,
                'is_active'       => true,
                'is_default'      => false,
                'configuration_json' => IdCardTemplate::getDefaultConfig('visitor', 'portrait'),
            ],
        ];

        foreach ($templates as $tmpl) {
            IdCardTemplate::updateOrCreate(
                [
                    'card_type'   => $tmpl['card_type'],
                    'orientation' => $tmpl['orientation'],
                ],
                $tmpl
            );
        }
    }

    /**
     * Get or issue an ID Card for a Student
     */
    public function getOrCreateCardForStudent(Student $student, ?int $templateId = null, string $orientation = 'landscape', string $side = 'both'): IdCard
    {
        $currentYear = AcademicYear::current();
        
        $card = IdCard::where('linked_entity_type', Student::class)
            ->where('linked_entity_id', $student->id)
            ->whereIn('status', [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED, IdCard::STATUS_GENERATED])
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->latest('id')
            ->first();

        if ($card) {
            // Update orientation and side if requested
            if ($card->orientation !== $orientation || $card->side !== $side) {
                $card->orientation = $orientation;
                $card->side = $side;
                $card->saveQuietly();
            }
            return $card;
        }

        // Find template
        $template = $templateId ? IdCardTemplate::find($templateId) : null;
        if (!$template) {
            $template = IdCardTemplate::where('card_type', IdCard::TYPE_STUDENT)
                ->where('orientation', $orientation)
                ->where('is_active', true)
                ->first();
        }

        $enrollment = $student->currentEnrollment;
        $classModel = $enrollment?->class;
        $sectionModel = $enrollment?->section;

        $rollNo = $student->roll_number ?: str_pad($student->id % 100 ?: 1, 2, '0', STR_PAD_LEFT);
        $individualPhone = $student->father_mobile ?: ($student->mother_mobile ?: ($student->mobile ?: ($student->emergency_contact_mobile ?: '98420 00008')));
        $individualAddress = $student->residential_address ?: ($student->permanent_address ?: '420, Vakkil Thottam, Erode');

        $yearCode = $currentYear ? substr($currentYear->name, 0, 4) : date('Y');
        $cardNumber = "EPS-S-{$yearCode}-R" . str_pad($rollNo, 2, '0', STR_PAD_LEFT);

        // Check if card number exists, if so append sequence
        if (IdCard::where('card_number', $cardNumber)->exists()) {
            $cardNumber .= '-' . (IdCard::where('linked_entity_type', Student::class)->where('linked_entity_id', $student->id)->count() + 1);
        }

        $expiryDate = $currentYear && $currentYear->end_date 
            ? Carbon::parse($currentYear->end_date) 
            : Carbon::now()->addYear()->endOfMonth();

        $snapshot = [
            'name'               => $student->full_name,
            'roll_number'        => $rollNo,
            'admission_no'       => $student->admission_number ?: $student->admission_no,
            'class_name'         => $classModel?->name ?? 'II',
            'section_name'       => $sectionModel?->name ?? 'A',
            'academic_year'      => $currentYear?->name ?? '2026-2027',
            'dob'                => $student->dob?->format('d/m/Y'),
            'blood_group'        => $student->blood_group ?? 'B+ve',
            'individual_phone'   => $individualPhone,
            'parent_mobile'      => $individualPhone,
            'father_name'        => $student->father_name,
            'mother_name'        => $student->mother_name,
            'individual_address' => $individualAddress,
            'address'            => $individualAddress,
            'photo'              => $student->photo,
        ];

        $token = (string) Str::uuid();

        $card = IdCard::create([
            'card_number'           => $cardNumber,
            'card_type'             => IdCard::TYPE_STUDENT,
            'linked_entity_type'    => Student::class,
            'linked_entity_id'      => $student->id,
            'academic_year_id'      => $currentYear?->id,
            'template_id'           => $template?->id,
            'template_version'      => $template?->version ?? 1,
            'orientation'           => $orientation,
            'side'                  => $side,
            'issue_date'            => Carbon::today(),
            'expiry_date'           => $expiryDate,
            'status'                => IdCard::STATUS_ACTIVE,
            'reprint_count'         => 0,
            'qr_verification_token' => $token,
            'barcode_value'         => 'EPS-R' . str_pad($rollNo, 2, '0', STR_PAD_LEFT) . '-' . $yearCode,
            'field_snapshot_json'   => $snapshot,
            'generated_at'          => Carbon::now(),
            'issued_by'             => auth()->id(),
        ]);

        IdCardAuditLog::record(
            $card->id,
            'created',
            null,
            $card->toArray(),
            'Initial student ID card generated for academic year ' . ($currentYear?->name ?? date('Y'))
        );

        return $card;
    }

    /**
     * Get or issue an ID Card for a Staff member / Employee
     */
    public function getOrCreateCardForEmployee(Employee $employee, ?int $templateId = null, string $orientation = 'landscape', string $side = 'both', bool $allowInactiveOverride = false): IdCard
    {
        if (!$employee->is_active && !$allowInactiveOverride) {
            throw new \DomainException("Cannot issue ID card to inactive or relieved staff member '{$employee->full_name}' without authorized approval.");
        }

        $card = IdCard::where('linked_entity_type', Employee::class)
            ->where('linked_entity_id', $employee->id)
            ->whereIn('status', [IdCard::STATUS_ACTIVE, IdCard::STATUS_ISSUED, IdCard::STATUS_GENERATED])
            ->latest('id')
            ->first();

        if ($card) {
            if ($card->orientation !== $orientation || $card->side !== $side) {
                $card->orientation = $orientation;
                $card->side = $side;
                $card->saveQuietly();
            }
            return $card;
        }

        $template = $templateId ? IdCardTemplate::find($templateId) : null;
        if (!$template) {
            $template = IdCardTemplate::where('card_type', IdCard::TYPE_STAFF)
                ->where('orientation', $orientation)
                ->where('is_active', true)
                ->first();
        }

        $empCode = $employee->employee_code ?: ('EMP-' . $employee->id);
        $cleanCode = preg_replace('/[^A-Za-z0-9]/', '', $empCode);
        $cardNumber = "EPS-E-" . strtoupper($cleanCode);

        if (IdCard::where('card_number', $cardNumber)->exists()) {
            $cardNumber .= '-' . (IdCard::where('linked_entity_type', Employee::class)->where('linked_entity_id', $employee->id)->count() + 1);
        }

        $expiryDate = Carbon::now()->addYears(2)->endOfYear();

        $snapshot = [
            'name'             => $employee->full_name,
            'employee_code'    => $employee->employee_code,
            'designation'      => $employee->designation,
            'department'       => $employee->department,
            'employment_type'  => $employee->employment_type ?? 'Permanent',
            'joining_date'     => $employee->joining_date?->format('d/m/Y'),
            'blood_group'      => $employee->blood_group,
            'mobile'           => $employee->mobile,
            'emergency_mobile' => $employee->emergency_contact_mobile ?? $employee->mobile,
            'photo'            => $employee->photo,
        ];

        $token = (string) Str::uuid();

        $card = IdCard::create([
            'card_number'           => $cardNumber,
            'card_type'             => IdCard::TYPE_STAFF,
            'linked_entity_type'    => Employee::class,
            'linked_entity_id'      => $employee->id,
            'template_id'           => $template?->id,
            'template_version'      => $template?->version ?? 1,
            'orientation'           => $orientation,
            'side'                  => $side,
            'issue_date'            => Carbon::today(),
            'expiry_date'           => $expiryDate,
            'status'                => IdCard::STATUS_ACTIVE,
            'reprint_count'         => 0,
            'qr_verification_token' => $token,
            'barcode_value'         => $cleanCode,
            'field_snapshot_json'   => $snapshot,
            'generated_at'          => Carbon::now(),
            'issued_by'             => auth()->id(),
        ]);

        IdCardAuditLog::record(
            $card->id,
            'created',
            null,
            $card->toArray(),
            'Initial staff ID card generated for ' . $employee->full_name
        );

        return $card;
    }

    /**
     * Get or issue an ID Card for a Visitor
     */
    public function getOrCreateCardForVisitor(Visitor $visitor, ?int $templateId = null, string $orientation = 'landscape'): IdCard
    {
        $card = IdCard::where('linked_entity_type', Visitor::class)
            ->where('linked_entity_id', $visitor->id)
            ->latest('id')
            ->first();

        if ($card) {
            return $card;
        }

        $template = $templateId ? IdCardTemplate::find($templateId) : null;
        if (!$template) {
            $template = IdCardTemplate::where('card_type', IdCard::TYPE_VISITOR)
                ->where('orientation', $orientation)
                ->where('is_active', true)
                ->first();
        }

        $passNum = $visitor->pass_number ?: ('VP-' . date('Ymd') . '-' . $visitor->id);
        $cardNumber = "EPS-V-" . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $passNum));

        $expiryDate = $visitor->expected_exit_time ? Carbon::parse($visitor->expected_exit_time) : Carbon::now()->endOfDay();

        $snapshot = [
            'visitor_name'    => $visitor->visitor_name,
            'pass_number'     => $visitor->pass_number,
            'category'        => $visitor->category,
            'company_name'    => $visitor->company_name,
            'whom_to_meet'    => $visitor->whom_to_meet,
            'purpose'         => $visitor->purpose,
            'in_time'         => $visitor->in_time ? Carbon::parse($visitor->in_time)->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A'),
            'expected_exit'   => $visitor->expected_exit_time ? Carbon::parse($visitor->expected_exit_time)->format('d/m/Y h:i A') : now()->endOfDay()->format('d/m/Y h:i A'),
            'visitor_phone'   => $visitor->visitor_phone,
            'photo'           => $visitor->visitor_photo,
        ];

        $token = (string) Str::uuid();

        $card = IdCard::create([
            'card_number'           => $cardNumber,
            'card_type'             => IdCard::TYPE_VISITOR,
            'linked_entity_type'    => Visitor::class,
            'linked_entity_id'      => $visitor->id,
            'template_id'           => $template?->id,
            'template_version'      => $template?->version ?? 1,
            'orientation'           => $orientation,
            'side'                  => 'front',
            'issue_date'            => Carbon::today(),
            'expiry_date'           => $expiryDate,
            'status'                => IdCard::STATUS_ACTIVE,
            'reprint_count'         => 0,
            'qr_verification_token' => $token,
            'barcode_value'         => preg_replace('/[^A-Za-z0-9]/', '', $passNum),
            'field_snapshot_json'   => $snapshot,
            'generated_at'          => Carbon::now(),
            'issued_by'             => auth()->id(),
        ]);

        IdCardAuditLog::record(
            $card->id,
            'created',
            null,
            $card->toArray(),
            'Visitor ID pass generated for ' . $visitor->visitor_name . ' (' . $passNum . ')'
        );

        return $card;
    }

    /**
     * Record a reprint for an existing ID Card
     */
    public function reprintCard(IdCard $card, string $reason): IdCard
    {
        $prev = [
            'reprint_count'  => $card->reprint_count,
            'reprint_reason' => $card->reprint_reason,
            'updated_at'     => $card->updated_at,
        ];

        $card->increment('reprint_count');
        $card->reprint_reason = $reason;
        $card->save();

        IdCardAuditLog::record(
            $card->id,
            'reprinted',
            $prev,
            ['reprint_count' => $card->reprint_count, 'reprint_reason' => $reason],
            $reason
        );

        return $card;
    }

    /**
     * Replace a lost or damaged ID Card with a new active card and deactivate the old one
     */
    public function replaceCard(IdCard $oldCard, string $reason): IdCard
    {
        $oldPrev = ['status' => $oldCard->status];
        $oldCard->status = IdCard::STATUS_REPLACED;
        $oldCard->save();

        IdCardAuditLog::record(
            $oldCard->id,
            'replaced',
            $oldPrev,
            ['status' => IdCard::STATUS_REPLACED],
            'Card replaced due to: ' . $reason
        );

        // Generate new card
        $newCard = $oldCard->replicate([
            'id', 'created_at', 'updated_at', 'qr_verification_token', 'reprint_count', 'reprint_reason'
        ]);

        $newCard->card_number = $oldCard->card_number . '-R' . ($oldCard->reprint_count + 1);
        $newCard->status = IdCard::STATUS_ACTIVE;
        $newCard->issue_date = Carbon::today();
        $newCard->qr_verification_token = (string) Str::uuid();
        $newCard->reprint_count = 0;
        $newCard->reprint_reason = 'Replacement of card #' . $oldCard->card_number . ' (' . $reason . ')';
        $newCard->generated_at = Carbon::now();
        $newCard->issued_by = auth()->id();
        $newCard->save();

        IdCardAuditLog::record(
            $newCard->id,
            'created',
            null,
            $newCard->toArray(),
            'Issued as replacement for #' . $oldCard->card_number . ' (' . $reason . ')'
        );

        return $newCard;
    }

    /**
     * Revoke an ID Card
     */
    public function revokeCard(IdCard $card, string $reason): IdCard
    {
        $prev = ['status' => $card->status];
        $card->status = IdCard::STATUS_REVOKED;
        $card->save();

        IdCardAuditLog::record(
            $card->id,
            'revoked',
            $prev,
            ['status' => IdCard::STATUS_REVOKED],
            $reason
        );

        return $card;
    }

    /**
     * Generate QR code SVG containing secure verification URL
     */
    public function generateQrCodeSvg(string $url, int $size = 90): string
    {
        try {
            return QrCode::format('svg')
                ->size($size)
                ->margin(0)
                ->errorCorrection('M')
                ->generate($url);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Generate precision Code 128 vector SVG barcode for linear card scanning
     */
    public function generateBarcodeSvg(string $text, int $height = 36, int $barWidth = 2, string $color = '#0f172a'): string
    {
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', // 0-9
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', // 10-19
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', // 20-29
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', // 30-39
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', // 40-49
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', // 50-59
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', // 60-69
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', // 70-79
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', // 80-89
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', // 90-99
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112' // 100-106
        ];

        $clean = strtoupper(trim($text));
        if ($clean === '') {
            $clean = 'EPS-CARD';
        }

        $symbols = [104]; // Code 128-B start
        $sum = 104;

        for ($i = 0; $i < strlen($clean); $i++) {
            $val = ord($clean[$i]) - 32;
            if ($val < 0 || $val > 95) $val = 0;
            $symbols[] = $val;
            $sum += $val * ($i + 1);
        }

        $symbols[] = $sum % 103;
        $symbols[] = 106; // Stop code

        $modules = '';
        foreach ($symbols as $sym) {
            $pat = $patterns[$sym] ?? $patterns[0];
            $isBar = true;
            for ($j = 0; $j < strlen($pat); $j++) {
                $w = (int)$pat[$j];
                $modules .= str_repeat($isBar ? '1' : '0', $w);
                $isBar = !$isBar;
            }
        }

        $totalModules = strlen($modules);
        $svgWidth = $totalModules * $barWidth;

        $rects = [];
        $currentWidth = 0;
        $currentStart = 0;

        for ($m = 0; $m < $totalModules; $m++) {
            if ($modules[$m] === '1') {
                if ($currentWidth === 0) {
                    $currentStart = $m * $barWidth;
                }
                $currentWidth += $barWidth;
            } else {
                if ($currentWidth > 0) {
                    $rects[] = "<rect x=\"{$currentStart}\" y=\"0\" width=\"{$currentWidth}\" height=\"{$height}\" fill=\"{$color}\" />";
                    $currentWidth = 0;
                }
            }
        }
        if ($currentWidth > 0) {
            $rects[] = "<rect x=\"{$currentStart}\" y=\"0\" width=\"{$currentWidth}\" height=\"{$height}\" fill=\"{$color}\" />";
        }

        $rectStr = implode('', $rects);
        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$svgWidth} {$height}\" width=\"100%\" height=\"{$height}\" preserveAspectRatio=\"none\">{$rectStr}</svg>";
    }
}
