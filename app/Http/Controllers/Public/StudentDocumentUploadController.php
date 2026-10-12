<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentDocumentUploadController extends Controller
{
    /**
     * Categories of documents available for upload
     */
    public static function getDocumentCategories(): array
    {
        return [
            'aadhaar' => [
                'type'        => 'aadhaar',
                'title'       => 'Aadhaar Card',
                'subtitle'    => 'Student or Parent Aadhaar Card copy (Front & Back)',
                'badge'       => 'Mandatory',
                'required'    => true,
                'color'       => 'blue',
                'icon'        => 'id',
                'description' => 'Upload clear scan/photo of 12-digit Aadhaar Card.',
            ],
            'birth_certificate' => [
                'type'        => 'birth_certificate',
                'title'       => 'Birth Certificate',
                'subtitle'    => 'Municipal or Panchayat official Birth Certificate',
                'badge'       => 'Mandatory',
                'required'    => true,
                'color'       => 'amber',
                'icon'        => 'calendar',
                'description' => 'Shows officially registered Date of Birth & Parents\' names.',
            ],
            'caste' => [
                'type'        => 'caste',
                'title'       => 'Community / Caste Certificate',
                'subtitle'    => 'Community certificate for OBC / SC / ST / MBC / BC quota',
                'badge'       => 'If Applicable',
                'required'    => false,
                'color'       => 'emerald',
                'icon'        => 'shield',
                'description' => 'Required for availing government scholarship and quota benefits.',
            ],
            'pan' => [
                'type'        => 'pan',
                'title'       => 'Parent PAN Card',
                'subtitle'    => "Father / Mother / Guardian's PAN Card copy",
                'badge'       => 'Tax / Accounts',
                'required'    => false,
                'color'       => 'purple',
                'icon'        => 'credit-card',
                'description' => 'Required for accounting, fee receipts, and 80G documentation.',
            ],
            'tc' => [
                'type'        => 'tc',
                'title'       => 'Transfer Certificate (TC)',
                'subtitle'    => 'Original TC from previous recognized school',
                'badge'       => 'Std 1 & Above',
                'required'    => false,
                'color'       => 'rose',
                'icon'        => 'document',
                'description' => 'Counter-signed by Education Dept (if from another state/board).',
            ],
            'marksheet' => [
                'type'        => 'marksheet',
                'title'       => 'Previous School Marksheet / Progress Card',
                'subtitle'    => 'Report card of the last qualifying academic examination',
                'badge'       => 'Academic Record',
                'required'    => false,
                'color'       => 'indigo',
                'icon'        => 'academic',
                'description' => 'Attested copy of previous standard final mark sheet.',
            ],
            'address_proof' => [
                'type'        => 'address_proof',
                'title'       => 'Income / Ration Card / Address Proof',
                'subtitle'    => 'Electricity bill, Gas connection, Ration Card, or Income Certificate',
                'badge'       => 'Verification',
                'required'    => false,
                'color'       => 'teal',
                'icon'        => 'home',
                'description' => 'Valid residential proof matching permanent or present address.',
            ],
            'photo' => [
                'type'        => 'photo',
                'title'       => 'Passport Size Photograph',
                'subtitle'    => 'Recent color passport-size photograph with white/light background',
                'badge'       => 'Mandatory',
                'required'    => true,
                'color'       => 'sky',
                'icon'        => 'camera',
                'description' => 'Used for Student ID Card, ERP profile, and official register.',
            ],
            'other' => [
                'type'        => 'other',
                'title'       => 'Other Supporting Documents',
                'subtitle'    => 'Medical fitness, sports achievements, extracurricular certificates',
                'badge'       => 'Optional',
                'required'    => false,
                'color'       => 'slate',
                'icon'        => 'folder',
                'description' => 'Any additional documents requested by the school admissions desk.',
            ],
        ];
    }

    /**
     * Display the public document upload portal for the student
     */
    public function show(string $token)
    {
        $student = Student::with([
            'currentEnrollment.class',
            'currentEnrollment.section',
            'currentEnrollment.academicYear'
        ])->where('document_token', $token)->firstOrFail();

        $categories = self::getDocumentCategories();
        $documents = StudentDocument::where('student_id', $student->id)
            ->get()
            ->keyBy('document_type');

        // Statistics
        $totalCategories = count($categories);
        $uploadedCount = $documents->count();
        $verifiedCount = $documents->where('status', 'verified')->count();
        $pendingCount  = $documents->where('status', 'pending')->count();

        $mandatoryCount = collect($categories)->where('required', true)->count();
        $mandatoryUploadedCount = collect($categories)->where('required', true)->filter(fn($c, $k) => isset($documents[$k]))->count();

        $school = SchoolSetting::first() ?? (object)[
            'school_name' => 'DASA EDUGROUP',
            'phone'       => '+91 98765 43210',
            'email'       => 'admissions@dasaedugroup.com',
            'website'     => 'www.dasaedugroup.com',
            'address'     => '123, Education City Campus, India'
        ];

        return view('public.student-document-upload', compact(
            'student', 'categories', 'documents', 'totalCategories',
            'uploadedCount', 'verifiedCount', 'pendingCount',
            'mandatoryCount', 'mandatoryUploadedCount', 'school'
        ));
    }

    /**
     * Handle document upload via the public QR portal
     */
    public function upload(Request $request, string $token)
    {
        $student = Student::where('document_token', $token)->firstOrFail();

        $request->validate([
            'document_type' => 'required|string|max:100',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // max 10MB
            'remarks'       => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        // Sanitize document type to alphanumeric slug to prevent path traversal
        $docType = preg_replace('/[^a-zA-Z0-9_\-]/', '', Str::slug((string) $request->document_type));
        if (empty($docType)) {
            $docType = 'document';
        }
        $safeExtension = $file->extension() ?: 'bin';

        // Store file with cryptographically random name and verified extension
        $fileName = $docType . '_' . time() . '_' . Str::random(12) . '.' . $safeExtension;
        $storedPath = $file->storeAs("students/{$student->id}/documents", $fileName, 'public');

        // If uploading student photo, also update the main student photo if not set or user wants it
        if ($docType === 'photo') {
            $student->update(['photo' => $storedPath]);
        }

        // Create or update StudentDocument entry
        $existingDoc = StudentDocument::where('student_id', $student->id)
            ->where('document_type', $docType)
            ->first();

        if ($existingDoc) {
            // Delete old file if exists
            if ($existingDoc->file_path && Storage::disk('public')->exists($existingDoc->file_path)) {
                Storage::disk('public')->delete($existingDoc->file_path);
            }

            $existingDoc->update([
                'file_path'     => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'status'        => 'pending',
                'remarks'       => $request->remarks ?: 'Re-uploaded via QR Document Portal on ' . now()->format('d M Y, h:i A'),
                'verified_by'   => null,
                'verified_at'   => null,
            ]);
            $document = $existingDoc;
        } else {
            $document = StudentDocument::create([
                'student_id'    => $student->id,
                'document_type' => $docType,
                'file_path'     => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'status'        => 'pending',
                'remarks'       => $request->remarks ?: 'Uploaded via QR Document Portal on ' . now()->format('d M Y, h:i A'),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Document uploaded successfully! School staff will verify your submission.',
                'document'      => $document,
                'file_url'      => asset('storage/' . $storedPath),
                'original_name' => $file->getClientOriginalName(),
                'status'        => 'pending',
            ]);
        }

        return back()->with('success', 'Document uploaded successfully! Our admissions department has received it.');
    }

    /**
     * Download or preview a document uploaded by student
     */
    public function downloadDocument(string $token, int $docId)
    {
        $student = Student::where('document_token', $token)->firstOrFail();
        $doc = StudentDocument::where('student_id', $student->id)->findOrFail($docId);

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            return Storage::disk('public')->response($doc->file_path, $doc->original_name ?? basename($doc->file_path));
        }

        if ($doc->file_path && Storage::disk('local')->exists($doc->file_path)) {
            return Storage::disk('local')->response($doc->file_path, $doc->original_name ?? basename($doc->file_path));
        }

        abort(404, 'Document file not found.');
    }

    /**
     * Generate & download the student's unique QR Code as an SVG/PNG badge
     */
    public function downloadQr(string $token)
    {
        $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])
            ->where('document_token', $token)
            ->firstOrFail();

        $url = route('public.student.documents', ['token' => $student->document_token]);
        $qrSvg = QrCode::size(300)->margin(2)->generate($url);

        $filename = 'QR_Admission_' . ($student->admission_number ?? $student->id) . '.svg';

        return response($qrSvg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Printable A6 / Card Slip for Admission Desk
     */
    public function printCard(string $token)
    {
        $student = Student::with([
            'currentEnrollment.class',
            'currentEnrollment.section',
            'currentEnrollment.academicYear'
        ])->where('document_token', $token)->firstOrFail();

        $url = route('public.student.documents', ['token' => $student->document_token]);
        $qrSvg = QrCode::size(240)->margin(1)->generate($url);

        $school = SchoolSetting::first() ?? (object)[
            'school_name' => 'DASA EDUGROUP',
            'phone'       => '+91 98765 43210',
            'email'       => 'admissions@dasaedugroup.com',
            'website'     => 'www.dasaedugroup.com',
            'address'     => '123, Education City Campus, India'
        ];

        return view('public.student-qr-print-card', compact('student', 'qrSvg', 'url', 'school'));
    }
}
