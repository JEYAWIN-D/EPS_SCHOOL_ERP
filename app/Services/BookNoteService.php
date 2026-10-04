<?php

namespace App\Services;

use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\AdmissionBookNoteItem;
use App\Models\BookNoteChecklist;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookNoteService
{
    public function __construct(
        protected ?UniformRuleService $uniformRuleService = null
    ) {
        $this->uniformRuleService = $uniformRuleService ?? app(UniformRuleService::class);
    }

    /**
     * Determine if a class is Senior Secondary (XI or XII).
     */
    public function isSeniorSecondary(int|Classes $class): bool
    {
        $classModel = $class instanceof Classes ? $class : Classes::find($class);
        if (!$classModel) {
            return false;
        }

        $numVal = (int) $classModel->numeric_value;
        if ($numVal === 11 || $numVal === 12) {
            return true;
        }

        $cleanName = strtoupper(trim($classModel->name ?? ''));
        return in_array($cleanName, ['XI', 'XII', '11', '12', 'GRADE XI', 'GRADE XII', 'CLASS XI', 'CLASS XII', 'XI STD', 'XII STD']);
    }

    /**
     * Generate standard-wise SKU for a book or note item.
     * Format: EPS-{CLASS}-{GROUP?}-{BK/NT/UNF}-{001}
     */
    public function generateSku(int|Classes $class, ?int $groupId, string $itemType, int $displayOrder = 1, ?int $existingId = null): string
    {
        $classModel = $class instanceof Classes ? $class : Classes::find($class);
        $rawClassName = strtoupper(trim($classModel?->name ?? 'CL'));

        $classCode = match($rawClassName) {
            'PRE-KG', 'PRE KG' => 'PKG',
            'LKG', 'JUNIOR KG' => 'LKG',
            'UKG', 'SENIOR KG' => 'UKG',
            default            => preg_replace('/[^A-Z0-9]/', '', $rawClassName) ?: 'CL'
        };

        $grpCode = '';
        if ($groupId) {
            $group = AcademicGroup::find($groupId);
            $code = strtoupper(trim($group?->code ?? ''));
            $grpCode = match($code) {
                'BIO'      => '-BIO',
                'CS'       => '-CS',
                'BIO_CS'   => '-BIO',
                'ACCOUNTS' => '-ACC',
                default    => '-' . substr(preg_replace('/[^A-Z0-9]/', '', $code ?: 'GRP'), 0, 3)
            };
        }

        $typeCode = match(strtoupper(trim($itemType))) {
            'BOOK'    => 'BK',
            'UNIFORM' => 'UNF',
            default   => 'NT'
        };
        $num = str_pad($displayOrder ?: ($existingId ? ($existingId % 100) : 1), 3, '0', STR_PAD_LEFT);

        return "EPS-{$classCode}{$grpCode}-{$typeCode}-{$num}";
    }

    /**
     * Get checklist for an academic year, class, optional group, and optional gender.
     */
    public function getChecklist(int $academicYearId, int $classId, ?int $groupId = null, bool $onlyActive = true, ?string $gender = null): array
    {
        $year = AcademicYear::find($academicYearId);
        if (!$year) {
            throw ValidationException::withMessages(['academic_year_id' => 'Academic Year not found.']);
        }

        $class = Classes::find($classId);
        if (!$class) {
            throw ValidationException::withMessages(['class_id' => 'Class not found.']);
        }

        $isSeniorSec = $this->isSeniorSecondary($class);

        if ($isSeniorSec) {
            if (!$groupId) {
                return [
                    'success'      => false,
                    'requiresGroup'=> true,
                    'message'      => 'Please select a group to view the Books, Notes & Uniforms checklist.',
                    'academicYear' => ['id' => $year->id, 'name' => $year->name],
                    'class'        => ['id' => $class->id, 'name' => $class->name],
                    'group'        => null,
                    'books'        => [],
                    'notes'        => [],
                    'uniforms'     => [],
                    'summary'      => ['totalBooks' => 0, 'totalNotes' => 0, 'totalUniforms' => 0, 'grandTotal' => 0],
                ];
            }

            $group = AcademicGroup::find($groupId);
            if (!$group) {
                throw ValidationException::withMessages(['group_id' => 'Invalid academic group selected.']);
            }
        } else {
            $groupId = null;
            $group = null;
        }

        $query = BookNoteChecklist::where('academic_year_id', $year->id)
            ->where('class_id', $class->id);

        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        if ($onlyActive) {
            $query->where('status', 'active');
        }

        $items = $query->orderBy('display_order', 'asc')
            ->orderBy('item_name', 'asc')
            ->get();

        $books = [];
        $notes = [];

        foreach ($items as $item) {
            $entry = [
                'id'           => $item->id,
                'sku'          => $item->sku ?: $this->generateSku($class, $groupId, $item->item_type, $item->display_order, $item->id),
                'name'         => $item->item_name,
                'quantity'     => (int) $item->quantity,
                'displayOrder' => (int) $item->display_order,
                'status'       => $item->status,
                'itemType'     => $item->item_type,
            ];

            if ($item->item_type === 'BOOK') {
                $books[] = $entry;
            } elseif ($item->item_type === 'NOTE') {
                $notes[] = $entry;
            }
        }

        // Uniform Items: prioritize database records if present, otherwise UniformRuleService
        $dbUniformItems = $items->where('item_type', 'UNIFORM');
        $uniforms = [];
        if ($dbUniformItems->isNotEmpty()) {
            $normGen = $gender ? $this->uniformRuleService->normalizeGender($gender) : null;
            foreach ($dbUniformItems as $item) {
                if ($normGen && $item->gender && $item->gender !== 'ALL' && $item->gender !== $normGen) {
                    continue;
                }
                $uniforms[] = [
                    'id'           => $item->id,
                    'sku'          => $item->sku ?: $this->uniformRuleService->generateSku($class, $item->gender ?? 'ALL', $item->display_order),
                    'name'         => $item->item_name,
                    'item_name'    => $item->item_name,
                    'quantity'     => (int) $item->quantity,
                    'displayOrder' => (int) $item->display_order,
                    'status'       => $item->status,
                    'itemType'     => 'UNIFORM',
                    'item_type'    => 'UNIFORM',
                    'gender'       => $item->gender,
                ];
            }
        } else {
            $uniformData = $this->uniformRuleService->getUniformsForClass($class, $gender);
            if ($gender) {
                $uniforms = $uniformData['active'];
            } else {
                $uniforms = array_merge($uniformData['boys'], $uniformData['girls']);
            }
        }

        $totalBooksCount = array_sum(array_column($books, 'quantity'));
        $totalNotesCount = array_sum(array_column($notes, 'quantity'));
        $totalUniformsCount = array_sum(array_column($uniforms, 'quantity'));

        return [
            'success'      => true,
            'requiresGroup'=> false,
            'academicYear' => [
                'id'   => $year->id,
                'name' => $year->name,
            ],
            'class' => [
                'id'   => $class->id,
                'name' => $class->name,
            ],
            'group' => $group ? [
                'id'   => $group->id,
                'name' => $group->name,
                'code' => $group->code,
            ] : null,
            'books'    => $books,
            'notes'    => $notes,
            'uniforms' => $uniforms,
            'summary'  => [
                'totalBooks'    => $totalBooksCount,
                'totalNotes'    => $totalNotesCount,
                'totalUniforms' => $totalUniformsCount,
                'grandTotal'    => $totalBooksCount + $totalNotesCount + $totalUniformsCount,
            ],
        ];
    }

    /**
     * Create a new Book or Note in the checklist.
     */
    public function createItem(array $data, ?int $userId = null): BookNoteChecklist
    {
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);
        $classId        = (int) ($data['class_id'] ?? 0);
        $class          = Classes::findOrFail($classId);
        $isSeniorSec    = $this->isSeniorSecondary($class);

        $groupId = null;
        if ($isSeniorSec) {
            if (empty($data['group_id'])) {
                throw ValidationException::withMessages(['group_id' => 'Academic group is required for Class XI and XII.']);
            }
            $groupId = (int) $data['group_id'];
        }

        $itemType = strtoupper(trim($data['item_type'] ?? ''));
        if (!in_array($itemType, ['BOOK', 'NOTE', 'UNIFORM'])) {
            throw ValidationException::withMessages(['item_type' => 'Item type must be BOOK, NOTE, or UNIFORM.']);
        }

        $gender = null;
        if (!empty($data['gender'])) {
            $g = strtoupper(trim($data['gender']));
            $gender = match($g) {
                'MALE', 'BOY', 'BOYS' => 'BOYS',
                'FEMALE', 'GIRL', 'GIRLS' => 'GIRLS',
                default => 'ALL'
            };
        } elseif ($itemType === 'UNIFORM') {
            $gender = 'ALL';
        }

        $itemName = trim($data['item_name'] ?? '');
        if ($itemName === '') {
            throw ValidationException::withMessages(['item_name' => 'Item name is required.']);
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be a positive integer.']);
        }

        // Duplicate prevention check
        $dupQuery = BookNoteChecklist::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('item_type', $itemType)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower($itemName)]);

        if ($gender !== null) {
            $dupQuery->where('gender', $gender);
        }

        if ($groupId !== null) {
            $dupQuery->where('group_id', $groupId);
        } else {
            $dupQuery->whereNull('group_id');
        }

        if ($dupQuery->exists()) {
            throw ValidationException::withMessages([
                'item_name' => "This {$itemType} ('{$itemName}') already exists in the selected Academic Year, Class and Group.",
            ]);
        }

        if (!empty($data['sku'])) {
            $sku = trim($data['sku']);
        } elseif ($itemType === 'UNIFORM') {
            $sku = $this->uniformRuleService->generateSku($class, $gender ?? 'ALL', (int) ($data['display_order'] ?? 1));
        } else {
            $sku = $this->generateSku($class, $groupId, $itemType, (int) ($data['display_order'] ?? 1));
        }

        return BookNoteChecklist::create([
            'academic_year_id' => $academicYearId,
            'class_id'         => $classId,
            'group_id'         => $groupId,
            'item_type'        => $itemType,
            'gender'           => $gender,
            'sku'              => $sku,
            'item_name'        => $itemName,
            'quantity'         => $quantity,
            'display_order'    => (int) ($data['display_order'] ?? 0),
            'status'           => $data['status'] ?? 'active',
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ]);
    }

    /**
     * Update an existing Book, Note, or Uniform in the checklist.
     */
    public function updateItem(int $id, array $data, ?int $userId = null): BookNoteChecklist
    {
        $item = BookNoteChecklist::findOrFail($id);

        $academicYearId = isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : $item->academic_year_id;
        $classId        = isset($data['class_id']) ? (int) $data['class_id'] : $item->class_id;
        $class          = Classes::findOrFail($classId);
        $isSeniorSec    = $this->isSeniorSecondary($class);

        $groupId = null;
        if ($isSeniorSec) {
            $groupId = isset($data['group_id']) ? (int) $data['group_id'] : $item->group_id;
            if (!$groupId) {
                throw ValidationException::withMessages(['group_id' => 'Academic group is required for Class XI and XII.']);
            }
        }

        $itemType = isset($data['item_type']) ? strtoupper(trim($data['item_type'])) : $item->item_type;
        if (!in_array($itemType, ['BOOK', 'NOTE', 'UNIFORM'])) {
            throw ValidationException::withMessages(['item_type' => 'Item type must be BOOK, NOTE, or UNIFORM.']);
        }

        $gender = $item->gender;
        if (array_key_exists('gender', $data)) {
            if (!empty($data['gender'])) {
                $g = strtoupper(trim($data['gender']));
                $gender = match($g) {
                    'MALE', 'BOY', 'BOYS' => 'BOYS',
                    'FEMALE', 'GIRL', 'GIRLS' => 'GIRLS',
                    default => 'ALL'
                };
            } else {
                $gender = ($itemType === 'UNIFORM') ? 'ALL' : null;
            }
        }

        $itemName = isset($data['item_name']) ? trim($data['item_name']) : $item->item_name;
        if ($itemName === '') {
            throw ValidationException::withMessages(['item_name' => 'Item name is required.']);
        }

        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : $item->quantity;
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be a positive integer.']);
        }

        // Duplicate prevention check excluding current item
        $dupQuery = BookNoteChecklist::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('item_type', $itemType)
            ->where('id', '!=', $id)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower($itemName)]);

        if ($gender !== null) {
            $dupQuery->where('gender', $gender);
        }

        if ($groupId !== null) {
            $dupQuery->where('group_id', $groupId);
        } else {
            $dupQuery->whereNull('group_id');
        }

        if ($dupQuery->exists()) {
            throw ValidationException::withMessages([
                'item_name' => "Another {$itemType} with the name '{$itemName}' already exists for this class configuration.",
            ]);
        }

        if (isset($data['sku']) && trim($data['sku']) !== '') {
            $sku = trim($data['sku']);
        } elseif ($item->sku) {
            $sku = $item->sku;
        } elseif ($itemType === 'UNIFORM') {
            $sku = $this->uniformRuleService->generateSku($classId, $gender ?? 'ALL', (int)($data['display_order'] ?? $item->display_order));
        } else {
            $sku = $this->generateSku($classId, $groupId, $itemType, (int)($data['display_order'] ?? $item->display_order), $item->id);
        }

        $item->update([
            'academic_year_id' => $academicYearId,
            'class_id'         => $classId,
            'group_id'         => $groupId,
            'item_type'        => $itemType,
            'gender'           => $gender,
            'sku'              => $sku,
            'item_name'        => $itemName,
            'quantity'         => $quantity,
            'display_order'    => isset($data['display_order']) ? (int) $data['display_order'] : $item->display_order,
            'status'           => $data['status'] ?? $item->status,
            'updated_by'       => $userId,
        ]);

        return $item->fresh(['academicYear', 'class', 'group']);
    }

    /**
     * Delete or deactivate record safely to protect historical admissions.
     */
    public function deleteOrDeactivate(int $id): array
    {
        $item = BookNoteChecklist::findOrFail($id);

        $hasAdmissions = AdmissionBookNoteItem::where('source_checklist_id', $id)->exists();

        if ($hasAdmissions) {
            $item->update(['status' => 'inactive']);
            return [
                'action'  => 'deactivated',
                'message' => "Record deactivated rather than deleted because it is linked to past student admissions.",
                'item'    => $item,
            ];
        }

        $item->delete();
        return [
            'action'  => 'deleted',
            'message' => "Record deleted successfully.",
        ];
    }

    /**
     * Toggle status active/inactive.
     */
    public function toggleStatus(int $id, ?int $userId = null): BookNoteChecklist
    {
        $item = BookNoteChecklist::findOrFail($id);
        $newStatus = $item->status === 'active' ? 'inactive' : 'active';
        $item->update([
            'status'     => $newStatus,
            'updated_by' => $userId,
        ]);

        return $item;
    }

    /**
     * Create an admission-level snapshot of books, notes, and uniforms.
     */
    public function snapshotForAdmission(
        int $academicYearId,
        int $classId,
        ?int $groupId,
        int $admissionId,
        ?int $studentId = null,
        ?int $enquiryId = null,
        ?array $issuedItemMap = null,
        ?int $userId = null,
        ?string $gender = null
    ): int {
        $checklist = $this->getChecklist($academicYearId, $classId, $groupId, true, $gender);

        if (!$checklist['success']) {
            return 0;
        }

        $allItems = array_merge($checklist['books'], $checklist['notes']);
        $inserted = 0;

        foreach ($allItems as $item) {
            $itemId = (int) $item['id'];
            $prescribedQty = (int) $item['quantity'];

            // Determine issued quantity
            $issuedQty = 0;
            if ($issuedItemMap !== null) {
                if (isset($issuedItemMap[$itemId])) {
                    $issuedQty = min($prescribedQty, max(0, (int) $issuedItemMap[$itemId]));
                } elseif (in_array($itemId, $issuedItemMap, true)) {
                    $issuedQty = $prescribedQty;
                }
            }

            $isIssued = ($issuedQty >= $prescribedQty && $prescribedQty > 0);

            AdmissionBookNoteItem::create([
                'admission_id'        => $admissionId,
                'student_id'          => $studentId,
                'enquiry_id'          => $enquiryId,
                'source_checklist_id' => $itemId,
                'item_type'           => $item['itemType'],
                'sku'                 => $item['sku'] ?? null,
                'item_name'           => $item['name'],
                'quantity'            => $prescribedQty,
                'issued_quantity'     => $issuedQty,
                'is_issued'           => $isIssued,
                'issued_at'           => $isIssued ? now() : null,
                'issued_by'           => $isIssued ? $userId : null,
            ]);
            $inserted++;
        }

        // Snapshot Uniform items based on student standard and gender
        $effGender = $gender ?: ($studentId ? Student::where('id', $studentId)->value('gender') : null);
        if ($effGender) {
            $uniformItems = $this->uniformRuleService->getUniformsForClass($classId, $effGender)['active'] ?? [];
            $normGender = $this->uniformRuleService->normalizeGender($effGender);

            foreach ($uniformItems as $uItem) {
                $prescribedQty = (int) $uItem['quantity'];
                $sku = $uItem['sku'];
                $issuedQty = 0;

                if ($issuedItemMap !== null) {
                    if (isset($issuedItemMap[$sku])) {
                        $issuedQty = min($prescribedQty, max(0, (int) $issuedItemMap[$sku]));
                    } elseif (isset($issuedItemMap[$uItem['name']])) {
                        $issuedQty = min($prescribedQty, max(0, (int) $issuedItemMap[$uItem['name']]));
                    } elseif (in_array($sku, $issuedItemMap, true) || in_array($uItem['name'], $issuedItemMap, true)) {
                        $issuedQty = $prescribedQty;
                    }
                }

                $isIssued = ($issuedQty >= $prescribedQty && $prescribedQty > 0);

                AdmissionBookNoteItem::create([
                    'admission_id'        => $admissionId,
                    'student_id'          => $studentId,
                    'enquiry_id'          => $enquiryId,
                    'source_checklist_id' => null,
                    'item_type'           => 'UNIFORM',
                    'gender'              => $normGender,
                    'sku'                 => $sku,
                    'item_name'           => $uItem['name'],
                    'quantity'            => $prescribedQty,
                    'issued_quantity'     => $issuedQty,
                    'is_issued'           => $isIssued,
                    'issued_at'           => $isIssued ? now() : null,
                    'issued_by'           => $isIssued ? $userId : null,
                ]);
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Aggregate Class Cards for the Card-based UI
     */
    public function getClassCardsData(int $academicYearId, ?string $search = null): array
    {
        $classes = Classes::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('numeric_value')
            ->get();

        $groups = AcademicGroup::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $cards = [];

        foreach ($classes as $cls) {
            $isSeniorSec = $this->isSeniorSecondary($cls);

            if (!$isSeniorSec) {
                $query = BookNoteChecklist::where('academic_year_id', $academicYearId)
                    ->where('class_id', $cls->id)
                    ->whereNull('group_id');

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('item_name', 'ilike', "%{$search}%")
                          ->orWhere('sku', 'ilike', "%{$search}%");
                    });
                }

                $items = $query->orderBy('display_order')->orderBy('item_name')->get();

                $books = $items->where('item_type', 'BOOK');
                $notes = $items->where('item_type', 'NOTE');
                $unfs  = $items->where('item_type', 'UNIFORM');

                $booksCount = $books->count();
                $booksQty = (int) $books->sum('quantity');
                $notesCount = $notes->count();
                $notesQty = (int) $notes->sum('quantity');
                $totalQty = $booksQty + $notesQty;

                if ($search && $items->isEmpty() && !str_contains(strtolower($cls->name), strtolower($search))) {
                    continue;
                }

                if ($unfs->isNotEmpty()) {
                    $boysUnfs = $unfs->filter(fn($u) => in_array($u->gender, ['BOYS', 'ALL', null]));
                    $girlsUnfs = $unfs->filter(fn($u) => in_array($u->gender, ['GIRLS', 'ALL', null]));
                    $uniformsBoysQty = (int) $boysUnfs->sum('quantity');
                    $uniformsGirlsQty = (int) $girlsUnfs->sum('quantity');
                    $uniformsCount = max($boysUnfs->count(), $girlsUnfs->count());
                    $sampleUniforms = $boysUnfs->take(3)->map(fn($u) => [
                        'id'   => $u->id,
                        'sku'  => $u->sku ?: $this->uniformRuleService->generateSku($cls, 'BOYS', $u->display_order),
                        'name' => $u->item_name,
                        'qty'  => $u->quantity,
                    ])->values()->all();
                    $tier = $this->uniformRuleService->resolveTier($cls);
                } else {
                    $unfData = $this->uniformRuleService->getUniformsForClass($cls);
                    $uniformsBoysQty = $unfData['boys_qty'];
                    $uniformsGirlsQty = $unfData['girls_qty'];
                    $uniformsCount = count($unfData['boys']);
                    $sampleUniforms = array_slice($unfData['boys'], 0, 3);
                    $tier = $unfData['tier'];
                }

                $cards[] = [
                    'class_id'             => $cls->id,
                    'class_name'           => $cls->name,
                    'numeric_value'        => $cls->numeric_value,
                    'is_senior_secondary'  => false,
                    'total_items'          => $booksCount + $notesCount + $uniformsCount,
                    'books_count'          => $booksCount,
                    'books_qty'            => $booksQty,
                    'notes_count'          => $notesCount,
                    'notes_qty'            => $notesQty,
                    'uniforms_boys_qty'    => $uniformsBoysQty,
                    'uniforms_girls_qty'   => $uniformsGirlsQty,
                    'uniforms_count'       => $uniformsCount,
                    'uniform_tier'         => $tier,
                    'sample_uniforms'      => $sampleUniforms,
                    'grand_total_qty'      => $totalQty + $uniformsBoysQty,
                    'active_items_count'   => $items->where('status', 'active')->count(),
                    'sample_books'         => $books->take(3)->map(fn($b) => [
                        'id'   => $b->id,
                        'sku'  => $b->sku ?: $this->generateSku($cls, null, 'BOOK', $b->display_order, $b->id),
                        'name' => $b->item_name,
                        'qty'  => $b->quantity,
                    ])->values()->all(),
                    'sample_notes'         => $notes->take(3)->map(fn($n) => [
                        'id'   => $n->id,
                        'sku'  => $n->sku ?: $this->generateSku($cls, null, 'NOTE', $n->display_order, $n->id),
                        'name' => $n->item_name,
                        'qty'  => $n->quantity,
                    ])->values()->all(),
                    'groups'               => [],
                ];
            } else {
                // Senior Secondary (XI / XII)
                $groupData = [];
                $totalClassItems = 0;
                $totalClassQty = 0;
                $classHasMatch = false;

                foreach ($groups as $grp) {
                    $query = BookNoteChecklist::where('academic_year_id', $academicYearId)
                        ->where('class_id', $cls->id)
                        ->where('group_id', $grp->id);

                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('item_name', 'ilike', "%{$search}%")
                              ->orWhere('sku', 'ilike', "%{$search}%");
                        });
                    }

                    $items = $query->orderBy('display_order')->orderBy('item_name')->get();
                    if ($items->isNotEmpty()) {
                        $classHasMatch = true;
                    }

                    $books = $items->where('item_type', 'BOOK');
                    $notes = $items->where('item_type', 'NOTE');

                    $bCount = $books->count();
                    $bQty = (int) $books->sum('quantity');
                    $nCount = $notes->count();
                    $nQty = (int) $notes->sum('quantity');
                    $gQty = $bQty + $nQty;

                    $totalClassItems += $items->count();
                    $totalClassQty += $gQty;

                    $groupData[] = [
                        'group_id'          => $grp->id,
                        'group_name'        => $grp->name,
                        'group_code'        => $grp->code,
                        'group_description' => $grp->description,
                        'total_items'       => $items->count(),
                        'books_count'       => $bCount,
                        'books_qty'         => $bQty,
                        'notes_count'       => $nCount,
                        'notes_qty'         => $nQty,
                        'grand_total_qty'   => $gQty,
                        'active_items_count'=> $items->where('status', 'active')->count(),
                        'sample_books'      => $books->take(3)->map(fn($b) => [
                            'id'   => $b->id,
                            'sku'  => $b->sku ?: $this->generateSku($cls, $grp->id, 'BOOK', $b->display_order, $b->id),
                            'name' => $b->item_name,
                            'qty'  => $b->quantity,
                        ])->values()->all(),
                        'sample_notes'      => $notes->take(3)->map(fn($n) => [
                            'id'   => $n->id,
                            'sku'  => $n->sku ?: $this->generateSku($cls, $grp->id, 'NOTE', $n->display_order, $n->id),
                            'name' => $n->item_name,
                            'qty'  => $n->quantity,
                        ])->values()->all(),
                    ];
                }

                if ($search && !$classHasMatch && !str_contains(strtolower($cls->name), strtolower($search))) {
                    continue;
                }

                $unfData = $this->uniformRuleService->getUniformsForClass($cls);

                $cards[] = [
                    'class_id'             => $cls->id,
                    'class_name'           => $cls->name,
                    'numeric_value'        => $cls->numeric_value,
                    'is_senior_secondary'  => true,
                    'total_items'          => $totalClassItems + count($unfData['boys']),
                    'grand_total_qty'      => $totalClassQty + $unfData['boys_qty'],
                    'uniforms_boys_qty'    => $unfData['boys_qty'],
                    'uniforms_girls_qty'   => $unfData['girls_qty'],
                    'uniforms_count'       => count($unfData['boys']),
                    'uniform_tier'         => $unfData['tier'],
                    'sample_uniforms'      => array_slice($unfData['boys'], 0, 3),
                    'groups'               => $groupData,
                ];
            }
        }

        return $cards;
    }

    /**
     * Ensure student has their admission_book_note_items populated for their class/year.
     */
    public function syncStudentChecklist(Student $student, ?int $academicYearId = null): int
    {
        $yearId = $academicYearId ?: (AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id'));
        if (!$yearId) {
            return 0;
        }

        // Check if items already exist for this student
        $existingCount = AdmissionBookNoteItem::where('student_id', $student->id)->count();
        $hasUniforms = AdmissionBookNoteItem::where('student_id', $student->id)->where('item_type', 'UNIFORM')->exists();

        // Find active enrollment
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_year_id', $yearId)
            ->first() ?? $student->currentEnrollment;

        // If items already exist but uniforms were never snapshotted, backfill uniforms
        if ($existingCount > 0 && !$hasUniforms && $student->gender && $enrollment?->class_id) {
            $class = Classes::find($enrollment->class_id);
            if ($class) {
                $unfItems = $this->uniformRuleService->getUniformsForClass($class, $student->gender)['active'] ?? [];
                $normGen = $this->uniformRuleService->normalizeGender($student->gender);
                foreach ($unfItems as $u) {
                    AdmissionBookNoteItem::create([
                        'admission_id'        => $student->id,
                        'student_id'          => $student->id,
                        'enquiry_id'          => null,
                        'source_checklist_id' => null,
                        'item_type'           => 'UNIFORM',
                        'gender'              => $normGen,
                        'sku'                 => $u['sku'],
                        'item_name'           => $u['name'],
                        'quantity'            => (int) $u['quantity'],
                        'issued_quantity'     => 0,
                        'is_issued'           => false,
                    ]);
                }
            }
            return AdmissionBookNoteItem::where('student_id', $student->id)->count();
        }

        if ($existingCount > 0) {
            return $existingCount;
        }

        if (!$enrollment || !$enrollment->class_id) {
            return 0;
        }

        $class = Classes::find($enrollment->class_id);
        if (!$class) {
            return 0;
        }

        // Determine stream group if Class XI/XII
        $groupId = null;
        if ($this->isSeniorSecondary($class)) {
            $stStr = strtoupper($student->stream_group ?? $student->stream_group_allotted ?? '');
            if (str_contains($stStr, 'ACCOUNT') || str_contains($stStr, 'COMMERCE') || str_contains($stStr, 'GROUP C')) {
                $groupId = AcademicGroup::where('code', 'ACCOUNTS')->value('id');
            } elseif (str_contains($stStr, 'CS') || str_contains($stStr, 'COMPUTER')) {
                $groupId = AcademicGroup::where('code', 'CS')->value('id');
            } else {
                $groupId = AcademicGroup::where('code', 'BIO')->value('id') ?? AcademicGroup::where('code', 'BIO_CS')->value('id');
            }
        }

        $inserted = $this->snapshotForAdmission(
            $yearId,
            $class->id,
            $groupId,
            $student->id,
            $student->id,
            null,
            null,
            null,
            $student->gender
        );

        // Fallback to active year master checklist if current session has no items yet
        $currentYearId = AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id');
        if ($inserted === 0 && $yearId != $currentYearId && $currentYearId) {
            $inserted = $this->snapshotForAdmission(
                $currentYearId,
                $class->id,
                $groupId,
                $student->id,
                $student->id,
                null,
                null,
                null,
                $student->gender
            );
        }

        return $inserted;
    }

    /**
     * Get aggregate statistics on who has received books/notes and who needs remaining items.
     */
    public function getDistributionSummary(int $academicYearId, ?int $classId = null): array
    {
        $query = StudentEnrollment::where('academic_year_id', $academicYearId);
        if ($classId) {
            $query->where('class_id', $classId);
        }

        $enrollments = $query->get(['student_id', 'class_id']);
        $totalStudents = $enrollments->count();

        if ($totalStudents === 0) {
            return [
                'total_students'         => 0,
                'students_fully_issued'  => 0,
                'students_with_remaining'=> 0,
                'remaining_books_units'  => 0,
                'remaining_notes_units'  => 0,
                'total_remaining_units'  => 0,
                'total_issued_units'     => 0,
            ];
        }

        $studentIds = $enrollments->pluck('student_id')->unique()->toArray();
        $items = AdmissionBookNoteItem::whereIn('student_id', $studentIds)->get();
        $itemsGrouped = $items->groupBy('student_id');

        // Pre-fetch class checklist items in a single query for fallback
        $activeYearId = AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id');
        $masterItems = BookNoteChecklist::where(function($q) use ($academicYearId, $activeYearId) {
                $q->where('academic_year_id', $academicYearId);
                if ($activeYearId && $activeYearId != $academicYearId) {
                    $q->orWhere('academic_year_id', $activeYearId);
                }
            })
            ->where('status', 'active')
            ->get();

        $classSummaryCache = [];
        foreach ($masterItems as $mItem) {
            $cId = $mItem->class_id;
            if (!isset($classSummaryCache[$cId])) {
                $classSummaryCache[$cId] = ['books' => 0, 'notes' => 0];
            }
            if ($mItem->item_type === 'BOOK') {
                $classSummaryCache[$cId]['books'] += (int) $mItem->quantity;
            } else {
                $classSummaryCache[$cId]['notes'] += (int) $mItem->quantity;
            }
        }

        $remainingBooksUnits = 0;
        $remainingNotesUnits = 0;
        $remainingUniformsUnits = 0;
        $totalIssuedUnits = 0;
        $studentsFullyIssued = 0;
        $studentsWithRemaining = 0;

        foreach ($enrollments as $enr) {
            $sId = $enr->student_id;
            if ($itemsGrouped->has($sId)) {
                $sItems = $itemsGrouped->get($sId);
                $sRemBooks = 0;
                $sRemNotes = 0;
                $sRemUniforms = 0;
                foreach ($sItems as $it) {
                    $rem = max(0, (int)$it->quantity - (int)$it->issued_quantity);
                    $totalIssuedUnits += (int)$it->issued_quantity;
                    if ($it->item_type === 'BOOK') {
                        $sRemBooks += $rem;
                    } elseif ($it->item_type === 'UNIFORM') {
                        $sRemUniforms += $rem;
                    } else {
                        $sRemNotes += $rem;
                    }
                }
                $remainingBooksUnits += $sRemBooks;
                $remainingNotesUnits += $sRemNotes;
                $remainingUniformsUnits += $sRemUniforms;
                if (($sRemBooks + $sRemNotes + $sRemUniforms) > 0) {
                    $studentsWithRemaining++;
                } else {
                    $studentsFullyIssued++;
                }
            } else {
                // Not yet synced: all class items are pending
                $cId = $enr->class_id;
                $cBooks = $classSummaryCache[$cId]['books'] ?? 0;
                $cNotes = $classSummaryCache[$cId]['notes'] ?? 0;
                $remainingBooksUnits += $cBooks;
                $remainingNotesUnits += $cNotes;
                if (($cBooks + $cNotes) > 0) {
                    $studentsWithRemaining++;
                } else {
                    $studentsFullyIssued++;
                }
            }
        }

        return [
            'total_students'          => $totalStudents,
            'students_fully_issued'   => $studentsFullyIssued,
            'students_with_remaining' => $studentsWithRemaining,
            'remaining_books_units'   => $remainingBooksUnits,
            'remaining_notes_units'   => $remainingNotesUnits,
            'remaining_uniforms_units'=> $remainingUniformsUnits,
            'total_remaining_units'   => $remainingBooksUnits + $remainingNotesUnits + $remainingUniformsUnits,
            'total_issued_units'      => $totalIssuedUnits,
        ];
    }

    /**
     * Get student list with remaining books & notes status.
     * Filterable by status: 'all', 'pending' (needing remaining items), 'fully_issued'.
     */
    public function getDistributionStudents(
        int $academicYearId,
        ?int $classId = null,
        ?string $statusFilter = 'all',
        ?string $search = null,
        int $perPage = 25,
        int $page = 1
    ): array {
        $enrollmentQuery = StudentEnrollment::with(['student', 'class', 'section'])
            ->where('academic_year_id', $academicYearId);

        if ($classId) {
            $enrollmentQuery->where('class_id', $classId);
        }

        if ($search) {
            $q = trim($search);
            $enrollmentQuery->whereHas('student', function ($sq) use ($q) {
                $sq->where('first_name', 'like', "%{$q}%")
                   ->orWhere('last_name', 'like', "%{$q}%")
                   ->orWhere('admission_no', 'like', "%{$q}%")
                   ->orWhere('roll_number', 'like', "%{$q}%");
            });
        }

        $enrollments = $enrollmentQuery->get();
        if ($enrollments->isEmpty()) {
            return [
                'total'        => 0,
                'per_page'     => $perPage,
                'current_page' => 1,
                'last_page'    => 1,
                'data'         => [],
            ];
        }

        $studentIds = $enrollments->pluck('student_id')->unique()->toArray();
        $itemsGrouped = AdmissionBookNoteItem::whereIn('student_id', $studentIds)->get()->groupBy('student_id');

        // Master items map
        $activeYearId = AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id');
        $masterItems = BookNoteChecklist::where(function($q) use ($academicYearId, $activeYearId) {
                $q->where('academic_year_id', $academicYearId);
                if ($activeYearId && $activeYearId != $academicYearId) {
                    $q->orWhere('academic_year_id', $activeYearId);
                }
            })
            ->where('status', 'active')
            ->orderBy('display_order')
            ->get();

        $classMasterMap = [];
        foreach ($masterItems as $m) {
            $classMasterMap[$m->class_id][] = $m;
        }

        $studentList = [];

        foreach ($enrollments as $enr) {
            $student = $enr->student;
            if (!$student) {
                continue;
            }

            $sId = $student->id;
            $items = $itemsGrouped->get($sId);

            $booksPrescribed = 0;
            $booksIssued = 0;
            $notesPrescribed = 0;
            $notesIssued = 0;
            $uniformsPrescribed = 0;
            $uniformsIssued = 0;
            $pendingItems = [];

            if ($items && $items->isNotEmpty()) {
                foreach ($items as $it) {
                    $qty = (int) $it->quantity;
                    $iss = (int) $it->issued_quantity;
                    $rem = max(0, $qty - $iss);

                    if ($it->item_type === 'BOOK') {
                        $booksPrescribed += $qty;
                        $booksIssued += $iss;
                    } elseif ($it->item_type === 'UNIFORM') {
                        $uniformsPrescribed += $qty;
                        $uniformsIssued += $iss;
                    } else {
                        $notesPrescribed += $qty;
                        $notesIssued += $iss;
                    }

                    if ($rem > 0) {
                        $pendingItems[] = [
                            'name' => $it->item_name,
                            'type' => $it->item_type,
                            'sku'  => $it->sku,
                            'rem'  => $rem,
                        ];
                    }
                }
            } else {
                // Class master defaults
                $cMasters = $classMasterMap[$enr->class_id] ?? [];
                foreach ($cMasters as $m) {
                    $qty = (int) $m->quantity;
                    if ($m->item_type === 'BOOK') {
                        $booksPrescribed += $qty;
                    } else {
                        $notesPrescribed += $qty;
                    }
                    $pendingItems[] = [
                        'name' => $m->item_name,
                        'type' => $m->item_type,
                        'sku'  => $m->sku,
                        'rem'  => $qty,
                    ];
                }
                if ($enr->class_id) {
                    $cObj = Classes::find($enr->class_id);
                    if ($cObj) {
                        $uData = $this->uniformRuleService->getUniformsForClass($cObj, $student->gender ?: 'BOYS')['active'] ?? [];
                        foreach ($uData as $u) {
                            $qty = (int) $u['quantity'];
                            $uniformsPrescribed += $qty;
                            $pendingItems[] = [
                                'name' => $u['name'],
                                'type' => 'UNIFORM',
                                'sku'  => $u['sku'],
                                'rem'  => $qty,
                            ];
                        }
                    }
                }
            }

            $totalPrescribed = $booksPrescribed + $notesPrescribed + $uniformsPrescribed;
            $totalIssued = $booksIssued + $notesIssued + $uniformsIssued;
            $totalRemaining = max(0, $totalPrescribed - $totalIssued);

            $status = 'pending';
            $distributionStatus = 'Pending Distribution';
            if ($totalPrescribed > 0 && $totalRemaining === 0) {
                $status = 'fully_issued';
                $distributionStatus = 'Distributed';
            } elseif ($totalIssued > 0 && $totalRemaining > 0) {
                $status = 'partially_issued';
                $distributionStatus = 'Partially Distributed';
            }

            // Payment status
            $paymentStatus = strtolower($student->payment_status ?? 'pending');
            $paymentStatusLabel = match($paymentStatus) {
                'paid' => 'Paid',
                'partially_paid', 'partial' => 'Partially Paid',
                default => 'Pending Payment'
            };

            // Status filter check
            if (($statusFilter === 'pending' || $statusFilter === 'pending_distribution') && $totalRemaining === 0) {
                continue;
            }
            if (($statusFilter === 'fully_issued' || $statusFilter === 'distributed') && $totalRemaining > 0) {
                continue;
            }
            if (($statusFilter === 'partially_issued' || $statusFilter === 'partially_distributed') && ($totalIssued === 0 || $totalRemaining === 0)) {
                continue;
            }

            $studentList[] = [
                'student_id'           => $student->id,
                'student_name'         => $student->full_name,
                'gender'               => $student->gender,
                'admission_no'         => $student->admission_no,
                'roll_no'              => $enr->roll_number ?: $student->roll_number,
                'class_id'             => $enr->class_id,
                'class_name'           => $enr->class->name ?? 'N/A',
                'section_name'         => $enr->section->name ?? '',
                'stream_group'         => $student->stream_group,
                'payment_status'       => $paymentStatus,
                'payment_status_label' => $paymentStatusLabel,
                'distribution_status'  => $distributionStatus,
                'books_prescribed'     => $booksPrescribed,
                'books_issued'         => $booksIssued,
                'books_remaining'      => max(0, $booksPrescribed - $booksIssued),
                'notes_prescribed'     => $notesPrescribed,
                'notes_issued'         => $notesIssued,
                'notes_remaining'      => max(0, $notesPrescribed - $notesIssued),
                'uniforms_prescribed'  => $uniformsPrescribed,
                'uniforms_issued'      => $uniformsIssued,
                'uniforms_remaining'   => max(0, $uniformsPrescribed - $uniformsIssued),
                'total_prescribed'     => $totalPrescribed,
                'total_issued'         => $totalIssued,
                'total_remaining'      => $totalRemaining,
                'status'               => $status,
                'pending_preview'      => array_slice($pendingItems, 0, 4),
                'pending_count'        => count($pendingItems),
            ];
        }

        // Sort: students with remaining items first, then by name
        usort($studentList, function ($a, $b) {
            if ($a['total_remaining'] > 0 && $b['total_remaining'] === 0) return -1;
            if ($a['total_remaining'] === 0 && $b['total_remaining'] > 0) return 1;
            return strcmp($a['student_name'], $b['student_name']);
        });

        $totalFiltered = count($studentList);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($studentList, $offset, $perPage);

        return [
            'total'        => $totalFiltered,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($totalFiltered / $perPage)),
            'data'         => $paginated,
        ];
    }

    /**
     * Get itemized student checklist with issuance controls.
     */
    public function getStudentChecklistDetails(int $studentId, ?int $academicYearId = null): array
    {
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($studentId);
        $yearId = $academicYearId ?: (AcademicYear::current()?->id ?? AcademicYear::latest('id')->value('id'));

        $this->syncStudentChecklist($student, $yearId);

        $items = AdmissionBookNoteItem::where('student_id', $student->id)
            ->orderBy('item_type')
            ->orderBy('id')
            ->get();

        $books = [];
        $notes = [];
        $uniforms = [];
        $totalPrescribed = 0;
        $totalIssued = 0;
        $totalRemaining = 0;

        foreach ($items as $item) {
            $qty = (int) $item->quantity;
            $iss = (int) $item->issued_quantity;
            $rem = max(0, $qty - $iss);

            $totalPrescribed += $qty;
            $totalIssued += $iss;
            $totalRemaining += $rem;

            $itemData = [
                'id'                 => $item->id,
                'source_checklist_id'=> $item->source_checklist_id,
                'item_type'          => $item->item_type,
                'gender'             => $item->gender,
                'sku'                => $item->sku,
                'item_name'          => $item->item_name,
                'quantity'           => $qty,
                'issued_quantity'    => $iss,
                'remaining_quantity' => $rem,
                'is_issued'          => $item->is_issued,
                'issued_at'          => $item->issued_at?->format('d M Y, h:i A'),
                'remarks'            => $item->remarks,
            ];

            if ($item->item_type === 'BOOK') {
                $books[] = $itemData;
            } elseif ($item->item_type === 'UNIFORM') {
                $uniforms[] = $itemData;
            } else {
                $notes[] = $itemData;
            }
        }

        $className = $student->currentEnrollment?->class?->name ?? 'Standard';
        $sectionName = $student->currentEnrollment?->section?->name ?? '';

        $paymentStatus = strtolower($student->payment_status ?? 'pending');
        $paymentStatusLabel = match($paymentStatus) {
            'paid' => 'Paid',
            'partially_paid', 'partial' => 'Partially Paid',
            default => 'Pending Payment'
        };

        $totFee = (float) ($student->total_admission_fee ?? 0);
        $paidFee = (float) ($student->admission_paid_amount ?? 0);
        $pendFee = (float) ($student->admission_pending_amount ?? max(0, $totFee - $paidFee));
        $isFeeCleared = ($paymentStatus === 'paid') || ($totFee > 0 && $pendFee <= 0 && $paidFee >= $totFee);

        $distributionStatus = ($totalPrescribed > 0 && $totalRemaining === 0) ? 'Distributed' : ($totalIssued > 0 ? 'Partially Distributed' : 'Pending Distribution');

        return [
            'student' => [
                'id'                       => $student->id,
                'name'                     => $student->full_name,
                'gender'                   => $student->gender,
                'admission_no'             => $student->admission_no,
                'roll_no'                  => $student->roll_number,
                'class_name'               => $className,
                'section_name'             => $sectionName,
                'stream_group'             => $student->stream_group,
                'mobile'                   => $student->mobile ?? $student->father_mobile,
                'payment_status'           => $paymentStatus,
                'payment_status_label'     => $paymentStatusLabel,
                'total_admission_fee'      => $totFee,
                'admission_paid_amount'    => $paidFee,
                'admission_pending_amount' => $pendFee,
                'payment_mode'             => $student->payment_mode,
                'payment_date'             => $student->payment_date ? \Carbon\Carbon::parse($student->payment_date)->format('d M Y') : null,
                'payment_account'          => $student->payment_account,
                'is_fee_cleared'           => $isFeeCleared,
                'dress_size'               => $student->dress_size,
            ],
            'summary' => [
                'total_prescribed'         => $totalPrescribed,
                'total_issued'             => $totalIssued,
                'total_remaining'          => $totalRemaining,
                'status'                   => ($totalPrescribed > 0 && $totalRemaining === 0) ? 'fully_issued' : ($totalIssued > 0 ? 'partially_issued' : 'pending'),
                'distribution_status'      => $distributionStatus,
                'payment_status'           => $paymentStatus,
                'payment_status_label'     => $paymentStatusLabel,
                'total_admission_fee'      => $totFee,
                'admission_paid_amount'    => $paidFee,
                'admission_pending_amount' => $pendFee,
                'is_fee_cleared'           => $isFeeCleared,
            ],
            'books'    => $books,
            'notes'    => $notes,
            'uniforms' => $uniforms,
        ];
    }

    /**
     * Update issued quantity for a specific student checklist item.
     */
    public function updateItemIssuance(int $itemId, int $issuedQuantity, ?string $remarks = null, ?int $userId = null): AdmissionBookNoteItem
    {
        $item = AdmissionBookNoteItem::findOrFail($itemId);
        $prescribedQty = (int) $item->quantity;
        $issued = min($prescribedQty, max(0, $issuedQuantity));
        $isIssued = ($issued >= $prescribedQty && $prescribedQty > 0);

        $item->update([
            'issued_quantity' => $issued,
            'is_issued'       => $isIssued,
            'issued_at'       => $issued > 0 ? now() : null,
            'issued_by'       => $issued > 0 ? $userId : null,
            'remarks'         => $remarks,
        ]);

        return $item->fresh();
    }

    /**
     * One-click action to issue all remaining books & notes for a student.
     */
    public function issueAllRemainingForStudent(int $studentId, ?int $userId = null, ?string $itemType = null): int
    {
        $query = AdmissionBookNoteItem::where('student_id', $studentId)
            ->whereRaw('issued_quantity < quantity');

        if ($itemType && in_array(strtoupper($itemType), ['NOTE', 'BOOK', 'UNIFORM'])) {
            $query->where('item_type', strtoupper($itemType));
        }

        $items = $query->get();

        $count = 0;
        foreach ($items as $item) {
            $item->update([
                'issued_quantity' => $item->quantity,
                'is_issued'       => true,
                'issued_at'       => now(),
                'issued_by'       => $userId,
            ]);
            $count++;
        }

        return $count;
    }
}
