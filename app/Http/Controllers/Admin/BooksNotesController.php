<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\AcademicYear;
use App\Models\BookNoteChecklist;
use App\Models\Classes;
use App\Models\Student;
use App\Services\BookNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BooksNotesController extends Controller
{
    public function __construct(
        protected BookNoteService $bookNoteService
    ) {}

    /**
     * Display Settings → Books & Notes Management Page
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $currentYear   = AcademicYear::current() ?? $academicYears->first();
        $classes       = Classes::where('is_active', true)->orderBy('sort_order')->orderBy('numeric_value')->get();
        $groups        = AcademicGroup::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();

        return view('settings.books-notes.index', compact(
            'academicYears',
            'currentYear',
            'classes',
            'groups'
        ));
    }

    /**
     * API: Get aggregated Class Cards with standard-wise books, notes, and group breakdown
     */
    public function apiCards(Request $request): JsonResponse
    {
        $yearId = (int) ($request->query('academic_year_id') ?: $request->query('academicYearId'));
        if (!$yearId) {
            $yearId = AcademicYear::where('name', 'like', '%2026-2027%')->orWhere('name', 'like', '%2026-27%')->value('id') 
                ?? AcademicYear::current()?->id 
                ?? 0;
        }
        $search = $request->query('search');

        $cards = $this->bookNoteService->getClassCardsData($yearId, $search);
        $academicYear = AcademicYear::find($yearId);

        $totalTitles = 0;
        $totalBooks = 0;
        $totalNotes = 0;
        $totalUnits = 0;

        foreach ($cards as $c) {
            if (!$c['is_senior_secondary']) {
                $totalTitles += $c['total_items'];
                $totalBooks  += $c['books_count'];
                $totalNotes  += $c['notes_count'];
                $totalUnits  += $c['grand_total_qty'];
            } else {
                foreach ($c['groups'] as $g) {
                    $totalTitles += $g['total_items'];
                    $totalBooks  += $g['books_count'];
                    $totalNotes  += $g['notes_count'];
                    $totalUnits  += $g['grand_total_qty'];
                }
            }
        }

        return response()->json([
            'success'      => true,
            'academicYear' => $academicYear ? ['id' => $academicYear->id, 'name' => $academicYear->name] : null,
            'cards'        => $cards,
            'summary'      => [
                'totalClasses' => count($cards),
                'totalTitles'  => $totalTitles,
                'totalBooks'   => $totalBooks,
                'totalNotes'   => $totalNotes,
                'totalUnits'   => $totalUnits,
            ],
        ]);
    }

    /**
     * API: Get checklist for Academic Year + Class + Group (used by New Admission & Settings)
     */
    public function apiChecklist(Request $request): JsonResponse
    {
        $yearId  = (int) ($request->query('academicYearId') ?: $request->query('academic_year_id'));
        $classId = (int) ($request->query('classId') ?: $request->query('class_id'));
        $groupId = $request->query('groupId') ?: $request->query('group_id');
        $groupId = $groupId ? (int) $groupId : null;

        if (!$yearId) {
            $currentYear = AcademicYear::current();
            $yearId = $currentYear?->id ?? 0;
        }

        if (!$classId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a Class.',
                'books'   => [],
                'notes'   => [],
            ], 422);
        }

        try {
            $gender = $request->query('gender');
            $checklist = $this->bookNoteService->getChecklist($yearId, $classId, $groupId, true, $gender);
            return response()->json($checklist);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load checklist: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: List all book & note master items with filters
     */
    public function apiList(Request $request): JsonResponse
    {
        $query = BookNoteChecklist::with(['academicYear:id,name', 'class:id,name,numeric_value', 'group:id,name,code']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', (int) $request->academic_year_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->class_id);
        }

        if ($request->filled('group_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('group_id', (int) $request->group_id)
                  ->orWhere(function ($sq) {
                      $sq->whereNull('group_id')->where('item_type', 'UNIFORM');
                  });
            });
        } elseif ($request->has('without_group') && $request->boolean('without_group')) {
            $query->whereNull('group_id');
        }

        if ($request->filled('item_type') && in_array(strtoupper($request->item_type), ['BOOK', 'NOTE', 'UNIFORM'])) {
            $query->where('item_type', strtoupper($request->item_type));
        }

        if ($request->filled('gender') && in_array(strtoupper($request->gender), ['BOYS', 'GIRLS', 'ALL'])) {
            $gen = strtoupper($request->gender);
            if ($gen !== 'ALL') {
                $query->where(function ($q) use ($gen) {
                    $q->where('gender', $gen)->orWhereNull('gender')->orWhere('gender', 'ALL');
                });
            }
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('item_name', 'ilike', "%{$search}%");
        }

        $items = $query->orderBy('academic_year_id', 'desc')
            ->orderBy('class_id', 'asc')
            ->orderBy('group_id', 'asc')
            ->orderBy('item_type', 'asc')
            ->orderBy('display_order', 'asc')
            ->orderBy('item_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $items->count(),
            'data'    => $items,
        ]);
    }

    /**
     * API: Store a new Book, Note, or Uniform
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id'         => 'required|integer|exists:classes,id',
            'group_id'         => 'nullable|integer|exists:academic_groups,id',
            'item_type'        => 'required|string|in:BOOK,NOTE,UNIFORM,book,note,uniform',
            'gender'           => 'nullable|string|in:BOYS,GIRLS,ALL,boys,girls,all,male,female',
            'sku'              => 'nullable|string|max:100',
            'item_name'        => 'required|string|max:255',
            'quantity'         => 'required|integer|min:1',
            'display_order'    => 'nullable|integer',
            'status'           => 'nullable|string|in:active,inactive',
        ]);

        try {
            $item = $this->bookNoteService->createItem($validated, Auth::id());
            $item->load(['academicYear', 'class', 'group']);

            return response()->json([
                'success' => true,
                'message' => "{$item->item_type} '{$item->item_name}' added successfully.",
                'data'    => $item,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Show single item
     */
    public function apiShow(int $id): JsonResponse
    {
        $item = BookNoteChecklist::with(['academicYear', 'class', 'group'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $item,
        ]);
    }

    /**
     * API: Update Book, Note, or Uniform
     */
    public function apiUpdate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'sometimes|required|integer|exists:academic_years,id',
            'class_id'         => 'sometimes|required|integer|exists:classes,id',
            'group_id'         => 'nullable|integer|exists:academic_groups,id',
            'item_type'        => 'sometimes|required|string|in:BOOK,NOTE,UNIFORM,book,note,uniform',
            'gender'           => 'nullable|string|in:BOYS,GIRLS,ALL,boys,girls,all,male,female',
            'sku'              => 'nullable|string|max:100',
            'item_name'        => 'sometimes|required|string|max:255',
            'quantity'         => 'sometimes|required|integer|min:1',
            'display_order'    => 'nullable|integer',
            'status'           => 'nullable|string|in:active,inactive',
        ]);

        try {
            $item = $this->bookNoteService->updateItem($id, $validated, Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Item '{$item->item_name}' updated successfully.",
                'data'    => $item,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Delete or Deactivate Item
     */
    public function apiDestroy(int $id): JsonResponse
    {
        try {
            $result = $this->bookNoteService->deleteOrDeactivate($id);
            return response()->json(array_merge(['success' => true], $result));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Toggle status (active/inactive)
     */
    public function apiToggleStatus(int $id): JsonResponse
    {
        try {
            $item = $this->bookNoteService->toggleStatus($id, Auth::id());
            return response()->json([
                'success' => true,
                'message' => "Status changed to {$item->status}.",
                'data'    => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get active groups
     */
    public function apiGroups(): JsonResponse
    {
        $groups = AcademicGroup::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description']);

        return response()->json([
            'success' => true,
            'data'    => $groups,
        ]);
    }

    /**
     * API: Get distribution overview summary (total students, fully issued, students with remaining items)
     */
    public function apiDistributionSummary(Request $request): JsonResponse
    {
        $yearId = (int) ($request->query('academic_year_id') ?: AcademicYear::current()?->id ?: AcademicYear::latest('id')->value('id'));
        $classId = $request->query('class_id') ? (int) $request->query('class_id') : null;

        $summary = $this->bookNoteService->getDistributionSummary($yearId, $classId);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * API: Get students with remaining / issued books and notes
     */
    public function apiDistributionStudents(Request $request): JsonResponse
    {
        $yearId = (int) ($request->query('academic_year_id') ?: AcademicYear::current()?->id ?: AcademicYear::latest('id')->value('id'));
        $classId = $request->query('class_id') ? (int) $request->query('class_id') : null;
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $perPage = (int) ($request->query('per_page', 25));
        $page = (int) ($request->query('page', 1));

        $result = $this->bookNoteService->getDistributionStudents($yearId, $classId, $status, $search, $perPage, $page);

        return response()->json([
            'success'    => true,
            'students'   => $result['data'],
            'pagination' => [
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
            ],
        ]);
    }

    /**
     * API: Fast student search with fee verification summary
     */
    public function apiSearchStudents(Request $request): JsonResponse
    {
        $search = trim($request->query('query', $request->query('search', '')));
        $limit  = min(30, max(1, (int) $request->query('limit', 15)));

        if (strlen($search) < 1) {
            // Return top 15 students, prioritizing paid & partially paid students first
            $students = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
                ->orderByRaw("CASE WHEN payment_status = 'paid' THEN 1 WHEN payment_status = 'partially_paid' THEN 2 ELSE 3 END")
                ->orderBy('first_name')
                ->limit($limit)
                ->get();
        } else {
            $students = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
                ->where(function ($q) use ($search) {
                    $q->where('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%")
                      ->orWhere('admission_no', 'ilike', "%{$search}%")
                      ->orWhere('roll_number', 'ilike', "%{$search}%");
                })
                ->limit($limit)
                ->get();
        }

        $formatted = $students->map(function ($st) {
            $status = strtolower($st->payment_status ?? 'pending');
            $statusLabel = match($status) {
                'paid' => 'Paid',
                'partially_paid', 'partial' => 'Partially Paid',
                default => 'Pending Payment'
            };
            $totFee = (float) ($st->total_admission_fee ?? 0);
            $paidFee = (float) ($st->admission_paid_amount ?? 0);
            $pendFee = (float) ($st->admission_pending_amount ?? max(0, $totFee - $paidFee));
            $isCleared = ($status === 'paid') || ($totFee > 0 && $pendFee <= 0 && $paidFee >= $totFee);

            return [
                'id'                   => $st->id,
                'name'                 => $st->full_name,
                'admission_no'         => $st->admission_no,
                'roll_no'              => $st->currentEnrollment?->roll_number ?: $st->roll_number,
                'gender'               => $st->gender,
                'class_id'             => $st->currentEnrollment?->class_id,
                'class_name'           => $st->currentEnrollment?->class?->name ?? 'Standard',
                'section_name'         => $st->currentEnrollment?->section?->name ?? '',
                'stream_group'         => $st->stream_group,
                'payment_status'       => $status,
                'payment_status_label' => $statusLabel,
                'total_fee'            => $totFee,
                'paid_fee'             => $paidFee,
                'pending_fee'          => $pendFee,
                'payment_mode'         => $st->payment_mode,
                'payment_date'         => $st->payment_date ? \Carbon\Carbon::parse($st->payment_date)->format('d M Y') : null,
                'is_fee_cleared'       => $isCleared,
            ];
        });

        return response()->json([
            'success'  => true,
            'count'    => $formatted->count(),
            'students' => $formatted,
        ]);
    }

    /**
     * API: Get student itemized checklist details
     */
    public function apiStudentChecklist(int $studentId, Request $request): JsonResponse
    {
        $yearId = $request->query('academic_year_id') ? (int) $request->query('academic_year_id') : null;
        $details = $this->bookNoteService->getStudentChecklistDetails($studentId, $yearId);

        return response()->json([
            'success' => true,
            'data'    => $details,
        ]);
    }

    /**
     * API: Update single item issued quantity
     */
    public function apiIssueItem(Request $request): JsonResponse
    {
        $request->validate([
            'item_id'         => 'required|integer',
            'issued_quantity' => 'required|integer|min:0',
            'remarks'         => 'nullable|string|max:255',
        ]);

        $item = $this->bookNoteService->updateItemIssuance(
            (int) $request->item_id,
            (int) $request->issued_quantity,
            $request->remarks,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Item issuance updated successfully.',
            'item'    => $item,
        ]);
    }

    /**
     * API: Issue all remaining books & notes for student
     */
    public function apiIssueAll(int $studentId, Request $request): JsonResponse
    {
        $itemType = $request->input('item_type');
        $count = $this->bookNoteService->issueAllRemainingForStudent($studentId, Auth::id(), $itemType);

        $label = $itemType ? strtolower($itemType) . 's' : 'items';
        return response()->json([
            'success' => true,
            'message' => "Successfully issued all remaining {$label} ({$count} updated).",
            'count'   => $count,
        ]);
    }

    /**
     * Print issuance receipt / parent pass
     */
    public function slip(int $studentId, Request $request)
    {
        $yearId = $request->query('academic_year_id') ? (int) $request->query('academic_year_id') : null;
        $data = $this->bookNoteService->getStudentChecklistDetails($studentId, $yearId);

        return view('settings.books-notes.slip', compact('data'));
    }
}
