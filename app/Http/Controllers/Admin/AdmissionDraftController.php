<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionDraft;
use App\Models\AdmissionDraftDocument;
use App\Models\Classes;
use App\Models\Enquiry;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdmissionDraftController extends Controller
{
    /**
     * Display a listing of admission drafts.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $classId = $request->get('class_id');
        $search = trim((string) $request->get('search', ''));

        $query = AdmissionDraft::with(['creator', 'classModel', 'academicYear', 'enquiry', 'documents', 'convertedStudent'])
            ->latest('last_saved_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('draft_number', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%")
                  ->orWhere('parent_name', 'like', "%{$search}%")
                  ->orWhere('parent_mobile', 'like', "%{$search}%");
            });
        }

        $drafts = $query->paginate(20)->withQueryString();

        // Draft statistics
        $stats = [
            'total'          => AdmissionDraft::count(),
            'in_progress'    => AdmissionDraft::where('status', 'in_progress')->count(),
            'ready'          => AdmissionDraft::where('status', 'ready_for_submission')->count(),
            'failed'         => AdmissionDraft::where('status', 'submission_failed')->count(),
            'submitted'      => AdmissionDraft::where('status', 'submitted')->count(),
        ];

        $classes = Classes::activeCached();

        return view('admissions.drafts.index', compact('drafts', 'stats', 'classes', 'status', 'classId', 'search'));
    }

    /**
     * Save or Auto-Save Admission Draft (AJAX endpoint)
     */
    public function saveDraft(Request $request)
    {
        try {
            $draftId = $request->input('draft_id');
            $enquiryId = $request->input('enquiry_id');
            $formData = $request->input('form_data', []);
            $currentStep = (int) $request->input('current_step', 1);
            $idempotencyToken = $request->input('idempotency_token');

            // If form_data is sent as JSON string, decode it
            if (is_string($formData)) {
                $formData = json_decode($formData, true) ?: [];
            }

            // Extract core fields for indexing and quick retrieval
            $studentName = trim(($formData['first_name'] ?? '') . ' ' . ($formData['last_name'] ?? ''));
            $parentName  = $formData['parent_name'] ?? ($formData['father_name'] ?? null);
            $parentMobile= $formData['parent_mobile'] ?? ($formData['father_mobile'] ?? null);
            $classId     = !empty($formData['class_id']) && is_numeric($formData['class_id']) ? (int) $formData['class_id'] : null;
            $academicYearId = !empty($formData['academic_year_id']) ? (int) $formData['academic_year_id'] : AcademicYear::current()?->id;

            // Find existing draft or create new
            $draft = null;
            if ($draftId) {
                $draft = AdmissionDraft::find($draftId);
            }

            if (!$draft && $enquiryId) {
                // Check if user already has an active draft for this enquiry
                $draft = AdmissionDraft::where('enquiry_id', $enquiryId)
                    ->whereIn('status', ['in_progress', 'pending_documents', 'ready_for_submission', 'submission_failed'])
                    ->latest()
                    ->first();
            }

            if (!$draft) {
                $draft = new AdmissionDraft();
                $draft->draft_number = AdmissionDraft::generateDraftNumber();
                $draft->created_by = Auth::id();
                $draft->idempotency_token = $idempotencyToken ?: Str::uuid()->toString();
            }

            // Calculate completion percentage
            $completionPercentage = AdmissionDraft::calculateCompletion($formData);

            $draft->enquiry_id            = $enquiryId ?: ($draft->enquiry_id ?? null);
            $draft->academic_year_id      = $academicYearId;
            $draft->class_id              = $classId;
            $draft->student_name          = $studentName ?: ($draft->student_name ?? 'Incomplete Application');
            $draft->parent_name           = $parentName;
            $draft->parent_mobile         = $parentMobile;
            $draft->form_data             = $formData;
            $draft->completion_percentage = $completionPercentage;
            $draft->current_step          = $currentStep;
            $draft->last_saved_at         = now();

            // Set appropriate status
            if ($draft->status !== 'submitted') {
                $draft->status = ($completionPercentage >= 90) ? 'ready_for_submission' : 'in_progress';
            }

            $draft->save();

            return response()->json([
                'success'               => true,
                'draft_id'              => $draft->id,
                'draft_number'          => $draft->draft_number,
                'completion_percentage' => $draft->completion_percentage,
                'last_saved_at'         => $draft->last_saved_at->format('h:i:s A'),
                'time_formatted'        => $draft->last_saved_at->diffForHumans(),
                'status'                => $draft->status,
                'message'               => 'Draft saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if current user has an active unfinished draft (AJAX endpoint)
     */
    public function checkActiveDraft(Request $request)
    {
        $enquiryId = $request->get('enquiry_id');
        $userId = Auth::id();

        $query = AdmissionDraft::whereIn('status', ['in_progress', 'pending_documents', 'ready_for_submission', 'submission_failed'])
            ->where('created_at', '>=', now()->subDays(7));

        if ($enquiryId) {
            $query->where('enquiry_id', $enquiryId);
        } else {
            $query->where('created_by', $userId);
        }

        $draft = $query->latest('last_saved_at')->first();

        if ($draft) {
            return response()->json([
                'has_draft' => true,
                'draft'     => [
                    'id'                    => $draft->id,
                    'draft_number'          => $draft->draft_number,
                    'student_name'          => $draft->student_name,
                    'parent_name'           => $draft->parent_name,
                    'class_name'            => $draft->classModel?->name ?? '—',
                    'completion_percentage' => $draft->completion_percentage,
                    'last_saved_at'         => $draft->last_saved_at?->format('d M Y, h:i A'),
                    'time_ago'              => $draft->last_saved_at?->diffForHumans() ?? 'recently',
                ],
            ]);
        }

        return response()->json(['has_draft' => false]);
    }

    /**
     * Load full draft payload (AJAX endpoint)
     */
    public function loadDraft(int $id)
    {
        $draft = AdmissionDraft::with(['documents', 'enquiry', 'classModel'])->findOrFail($id);

        return response()->json([
            'success'               => true,
            'draft_id'              => $draft->id,
            'draft_number'          => $draft->draft_number,
            'enquiry_id'            => $draft->enquiry_id,
            'class_id'              => $draft->class_id,
            'completion_percentage' => $draft->completion_percentage,
            'current_step'          => $draft->current_step,
            'status'                => $draft->status,
            'form_data'             => $draft->form_data,
            'documents'             => $draft->documents->map(fn($d) => [
                'id'            => $d->id,
                'document_type' => $d->document_type,
                'filename'      => $d->original_filename,
                'url'           => $d->public_url,
                'file_size'     => $d->file_size_formatted,
            ]),
        ]);
    }

    /**
     * Upload Document for a Draft
     */
    public function uploadDraftDocument(Request $request)
    {
        $request->validate([
            'draft_id'      => 'required|exists:admission_drafts,id',
            'document_type' => 'required|string|max:60',
            'file'          => 'required|file|max:5120|mimes:jpeg,jpg,png,pdf,webp',
        ]);

        $draft = AdmissionDraft::findOrFail($request->draft_id);
        $file = $request->file('file');
        $docType = $request->document_type;

        // Delete existing draft doc of same type if re-uploading
        $existing = AdmissionDraftDocument::where('draft_id', $draft->id)
            ->where('document_type', $docType)
            ->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->stored_path);
            $existing->delete();
        }

        $storedPath = $file->store("admission_drafts/{$draft->id}", 'public');

        $doc = AdmissionDraftDocument::create([
            'draft_id'          => $draft->id,
            'document_type'     => $docType,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
            'mime_type'         => $file->getClientMimeType(),
            'file_size'         => $file->getSize(),
            'upload_status'     => 'uploaded',
            'uploaded_at'       => now(),
        ]);

        return response()->json([
            'success'  => true,
            'document' => [
                'id'            => $doc->id,
                'document_type' => $doc->document_type,
                'filename'      => $doc->original_filename,
                'url'           => $doc->public_url,
                'file_size'     => $doc->file_size_formatted,
            ],
            'message'  => 'Document uploaded to draft successfully.',
        ]);
    }

    /**
     * Delete a Draft Document
     */
    public function deleteDraftDocument(int $id)
    {
        $doc = AdmissionDraftDocument::findOrFail($id);
        Storage::disk('public')->delete($doc->stored_path);
        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft document removed.',
        ]);
    }

    /**
     * Delete or discard an admission draft
     */
    public function deleteDraft(int $id)
    {
        $draft = AdmissionDraft::with('documents')->findOrFail($id);

        // Remove uploaded files
        foreach ($draft->documents as $doc) {
            Storage::disk('public')->delete($doc->stored_path);
            $doc->delete();
        }

        $draft->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Admission draft discarded successfully.',
            ]);
        }

        return redirect()->route('admissions.drafts.index')
            ->with('success', "Admission draft {$draft->draft_number} was discarded successfully.");
    }
}
