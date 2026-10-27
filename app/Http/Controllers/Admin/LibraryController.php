<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LibraryOverdueMail;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\BookReservation;
use App\Models\DigitalResource;
use App\Models\Employee;
use App\Models\LibrarySetting;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Subject;
use App\Exports\StockRegisterExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LibraryController extends Controller
{
    public function index()
    {
        $statsData = \Illuminate\Support\Facades\Cache::remember('library_index_stats_v1', 120, function () {
            $today = today()->toDateString();
            $bookRow = DB::table('books')->selectRaw("
                COUNT(*) as total_books,
                COALESCE(SUM(total_copies), 0) as total_copies,
                COALESCE(SUM(available_copies), 0) as available
            ")->first();

            $issueRow = DB::table('book_issues')
                ->where('status', 'issued')
                ->selectRaw("
                    COUNT(*) as issued,
                    COUNT(CASE WHEN due_date < '{$today}' THEN 1 END) as overdue,
                    COALESCE(SUM(CASE WHEN due_date < '{$today}' THEN fine_amount ELSE 0 END), 0) as fine_amount
                ")->first();

            return [
                'stats' => [
                    'total_books'  => (int) ($bookRow->total_books ?? 0),
                    'total_copies' => (int) ($bookRow->total_copies ?? 0),
                    'available'    => (int) ($bookRow->available ?? 0),
                    'issued'       => (int) ($issueRow->issued ?? 0),
                    'overdue'      => (int) ($issueRow->overdue ?? 0),
                ],
                'totalFinesPending' => (float) ($issueRow->fine_amount ?? 0),
            ];
        });

        $stats = $statsData['stats'];
        $totalFinesPending = $statsData['totalFinesPending'];

        // Overdue issues for alert
        $overdueIssues = BookIssue::with(['book:id,title,accession_number', 'student:id,first_name,last_name,admission_no'])
            ->where('status', 'issued')
            ->where('due_date', '<', today())
            ->orderBy('due_date')
            ->limit(5)->get();

        $recentIssues = BookIssue::with(['book:id,title,accession_number', 'student:id,first_name,last_name,admission_no'])->latest()->take(8)->get();
        return view('library.index', compact('stats', 'recentIssues', 'overdueIssues', 'totalFinesPending'));
    }

    public function books(Request $request)
    {
        $books = Book::when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('title', 'like', "%$v%")
                  ->orWhere('author', 'like', "%$v%")
                  ->orWhere('accession_number', 'like', "%$v%");
            }))
            ->when($request->category, fn($q, $v) => $q->where('category', 'like', "%$v%"))
            ->when($request->available, fn($q) => $q->where('available_copies', '>', 0))
            ->orderBy('title')->paginate(25)->withQueryString();

        return view('library.books', compact('books'));
    }

    public function createBook()
    {
        return view('library.books-create');
    }

    public function isbnLookup(Request $request)
    {
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper($request->isbn ?? ''));
        if (strlen($isbn) < 10) {
            return response()->json(['error' => 'Invalid ISBN'], 422);
        }
        try {
            $url  = "https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data";
            $json = @file_get_contents($url);
            if ($json === false) {
                return response()->json(['error' => 'Could not reach Open Library API'], 503);
            }
            $data = json_decode($json, true);
            $key  = "ISBN:{$isbn}";
            if (empty($data[$key])) {
                return response()->json(['error' => 'Book not found for ISBN ' . $isbn], 404);
            }
            $book = $data[$key];
            $authors = collect($book['authors'] ?? [])->pluck('name')->implode(', ');
            $cover   = $book['cover']['medium'] ?? $book['cover']['small'] ?? null;
            return response()->json([
                'title'     => $book['title'] ?? '',
                'author'    => $authors,
                'publisher' => collect($book['publishers'] ?? [])->pluck('name')->first() ?? '',
                'year'      => substr($book['publish_date'] ?? '', -4),
                'pages'     => $book['number_of_pages'] ?? null,
                'subjects'  => collect($book['subjects'] ?? [])->pluck('name')->take(5)->implode(', '),
                'cover_url' => $cover,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lookup failed: ' . $e->getMessage()], 500);
        }
    }

    public function storeBook(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:200',
            'author'           => 'nullable|string|max:150',
            'publisher'        => 'nullable|string|max:150',
            'isbn'             => 'nullable|string|max:20',
            'publication_year' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
            'total_copies'     => 'required|integer|min:1',
            'purchase_price'   => 'nullable|numeric|min:0',
            'purchase_date'    => 'nullable|date',
            'location'         => 'nullable|string|max:50',
            'cover_image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('book-covers', 'public');
        }

        $book = Book::create(array_merge($validated, [
            'accession_number'  => $this->generateAccessionNumber(),
            'available_copies'  => $validated['total_copies'],
            'publish_year'      => $validated['publication_year'] ?? null,
            'cover_image'       => $coverPath,
        ]));

        return redirect()->route('library.books')
            ->with('success', '"' . $book->title . '" added to library.');
    }

    public function issue(Request $request)
    {
        $books          = Book::where('available_copies', '>', 0)->orderBy('title')->get();
        $students       = Student::where('status', 'active')->orderBy('first_name')->get();
        $employees      = Employee::where('is_active', true)->orderBy('first_name')->get();
        $studentLoanDays = LibrarySetting::forType('student')?->loan_days ?? 14;
        $staffLoanDays   = LibrarySetting::forType('staff')?->loan_days ?? 14;
        return view('library.issue', compact('books', 'students', 'employees', 'studentLoanDays', 'staffLoanDays'));
    }

    public function issueBook(Request $request)
    {
        $memberType = $request->input('member_type', 'student');

        if ($memberType === 'staff') {
            $request->validate([
                'book_id'     => 'required|exists:books,id',
                'employee_id' => 'required|exists:employees,id',
                'due_date'    => 'required|date|after:today',
            ]);
        } else {
            $request->validate([
                'book_id'    => 'required|exists:books,id',
                'student_id' => 'required|exists:students,id',
                'due_date'   => 'required|date|after:today',
            ]);
        }

        $book = Book::findOrFail($request->book_id);
        if ($book->available_copies < 1) {
            return back()->withErrors(['book_id' => 'No copies available.']);
        }

        // Block suspended library members
        if ($memberType === 'student') {
            $student = Student::findOrFail($request->student_id);
            if ($student->library_suspended) {
                return back()->withErrors(['student_id' => "Library access suspended for {$student->full_name}. Reason: " . ($student->library_suspension_reason ?: 'Not specified')]);
            }

            // Enforce per-member borrowing limit
            $setting  = LibrarySetting::forType('student');
            $maxBooks = $setting?->max_books ?? 2;
            $current  = BookIssue::where('student_id', $student->id)->where('status', 'issued')->count();
            if ($current >= $maxBooks) {
                return back()->withErrors(['student_id' => "Borrowing limit reached ({$maxBooks} books max). Return a book first."]);
            }
        }

        DB::transaction(function () use ($request, $book, $memberType) {
            BookIssue::create([
                'book_id'     => $book->id,
                'student_id'  => $memberType === 'student' ? $request->student_id : null,
                'employee_id' => $memberType === 'staff'   ? $request->employee_id : null,
                'issue_date'  => today(),
                'due_date'    => $request->due_date,
                'status'      => 'issued',
                'issued_by'   => Auth::id(),
            ]);
            $book->decrement('available_copies');
        });

        return redirect()->route('library.index')->with('success', 'Book issued successfully.');
    }

    public function returnBook(Request $request)
    {
        $request->validate(['issue_id' => 'required|exists:book_issues,id']);

        $issue = BookIssue::with('book')->findOrFail($request->issue_id);
        $fine  = 0;

        if (today()->gt($issue->due_date)) {
            $memberType = $issue->student_id ? 'student' : 'staff';
            $fineRate   = LibrarySetting::forType($memberType)?->fine_per_day ?? 2;
            $fine = today()->diffInDays($issue->due_date) * $fineRate;
        }

        DB::transaction(function () use ($issue, $fine) {
            $issue->update([
                'return_date' => today(),
                'status'      => 'returned',
                'fine_amount' => $fine,
                'returned_to' => Auth::id(),
            ]);
            $issue->book->increment('available_copies');
        });

        return back()->with('success', 'Book returned.' . ($fine > 0 ? " Fine: ₹$fine" : ''));
    }

    public function overdue()
    {
        $overdueIssues = BookIssue::with(['book', 'student'])
            ->where('status', 'issued')
            ->where('due_date', '<', today())
            ->orderBy('due_date')->paginate(25);

        return view('library.overdue', compact('overdueIssues'));
    }

    private function generateAccessionNumber(): string
    {
        $last = Book::max('id') ?? 0;
        return 'ACC-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }

    public function editBook(int $id)
    {
        $book = Book::findOrFail($id);
        return view('library.books-edit', compact('book'));
    }

    public function updateBook(Request $request, int $id)
    {
        $request->validate(['cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $book = Book::findOrFail($id);
        $data = $request->only(['title', 'author', 'publisher', 'isbn', 'total_copies', 'purchase_price', 'purchase_date', 'location', 'category']);
        if ($request->filled('publication_year')) {
            $data['publish_year'] = (int) $request->publication_year;
        }
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('book-covers', 'public');
        }
        $book->update($data);
        return back()->with('success', 'Book updated.');
    }

    public function reservations(Request $request)
    {
        $books   = Book::orderBy('title')->get();
        $members = Student::where('status', 'active')->orderBy('first_name')->get();
        $reservations = BookReservation::with(['book', 'student'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()->paginate(20)->withQueryString();
        return view('library.reservations', compact('reservations', 'books', 'members'));
    }

    public function storeReservation(Request $request)
    {
        $request->validate(['book_id' => 'required|exists:books,id', 'member_id' => 'required|exists:students,id']);
        $queuePos = BookReservation::where('book_id', $request->book_id)->where('status', 'pending')->count() + 1;
        BookReservation::create([
            'book_id'        => $request->book_id,
            'student_id'     => $request->member_id,
            'reserved_at'    => now(),
            'expiry_date'    => $request->expiry_date ?? now()->addDays(7),
            'status'         => 'pending',
            'queue_position' => $queuePos,
            'reserved_by'    => Auth::id(),
        ]);
        return back()->with('success', 'Book reserved. Queue position: ' . $queuePos . '.');
    }

    public function markReservationReady(int $id)
    {
        BookReservation::findOrFail($id)->update(['status' => 'ready']);
        return back()->with('success', 'Reservation marked ready.');
    }

    public function cancelReservation(int $id)
    {
        BookReservation::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->with('success', 'Reservation cancelled.');
    }

    public function fineWaiver(Request $request)
    {
        $issues = collect();
        if ($request->filled('search')) {
            $issues = BookIssue::with(['book', 'student'])
                ->where('fine_amount', '>', 0)
                ->whereHas('student', fn($q) => $q->where('first_name', 'like', '%'.$request->search.'%')->orWhere('last_name', 'like', '%'.$request->search.'%')->orWhere('admission_no', 'like', '%'.$request->search.'%'))
                ->get();
        }
        $recentWaivers = BookIssue::with(['book', 'student', 'waivedBy'])->where('fine_waived', true)->latest()->take(10)->get();
        return view('library.fine-waiver', compact('issues', 'recentWaivers'));
    }

    public function waiverApply(Request $request, int $id)
    {
        $request->validate(['waiver_reason' => 'required|string', 'waiver_amount' => 'required|numeric|min:0']);
        BookIssue::findOrFail($id)->update([
            'fine_waived'    => true,
            'waiver_amount'  => $request->waiver_amount,
            'waiver_reason'  => $request->waiver_reason,
            'waived_by'      => Auth::id(),
            'waived_at'      => now(),
        ]);
        return back()->with('success', 'Fine waiver applied.');
    }

    public function collectFineViaFee(Request $request, int $issueId)
    {
        $issue = BookIssue::with(['student', 'book'])->findOrFail($issueId);
        if (!$issue->fine_amount || $issue->fine_amount <= 0) {
            return back()->with('error', 'No fine to collect for this issue.');
        }
        if ($issue->fine_paid) {
            return back()->with('error', 'Fine has already been collected.');
        }
        $netFine = $issue->fine_waived
            ? max(0, $issue->fine_amount - ($issue->waiver_amount ?? 0))
            : $issue->fine_amount;
        if ($netFine <= 0) {
            return back()->with('error', 'Fine is fully waived; nothing to collect.');
        }

        // Find or create a "Library Fine" fee head
        $feeHead = \App\Models\FeeHead::firstOrCreate(
            ['name' => 'Library Fine'],
            ['fee_type' => 'library_fine', 'is_active' => true]
        );

        $currentYear = \App\Models\AcademicYear::current();
        $enrollment  = \App\Models\StudentEnrollment::where('student_id', $issue->student_id)
            ->where('status', 'active')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->first();

        $receiptNo = 'LF-' . strtoupper(uniqid());
        \App\Models\FeePayment::create([
            'student_id'      => $issue->student_id,
            'enrollment_id'   => $enrollment?->id,
            'fee_head_id'     => $feeHead->id,
            'academic_year_id'=> $currentYear?->id,
            'receipt_number'  => $receiptNo,
            'payment_date'    => today(),
            'amount'          => $netFine,
            'amount_paid'     => $netFine,
            'total_paid'      => $netFine,
            'payment_mode'    => $request->payment_mode ?? 'cash',
            'remarks'         => 'Library fine: ' . $issue->book?->title . ' (Issue #' . $issue->id . ')',
            'collected_by'    => Auth::id(),
        ]);
        $issue->update(['fine_paid' => true]);

        return back()->with('success', "Library fine ₹{$netFine} collected and recorded in Fee module. Receipt: {$receiptNo}.");
    }

    public function digitalResources(Request $request)
    {
        $subjects  = Subject::orderBy('name')->get();
        $classes   = Classes::active()->get();
        $resources = DigitalResource::with(['subject', 'class'])
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->subject_id, fn($q, $v) => $q->where('subject_id', $v))
            ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
            ->when($request->access, fn($q, $v) => $v === 'all'
                ? $q->whereNull('class_id')
                : $q->whereNotNull('class_id'))
            ->when($request->search, fn($q, $v) => $q->where('title', 'like', "%$v%"))
            ->latest()->paginate(12)->withQueryString();
        return view('library.digital-resources', compact('resources', 'subjects', 'classes'));
    }

    public function storeDigital(Request $request)
    {
        $request->validate(['title' => 'required|string|max:200', 'type' => 'required|in:ebook,video,audio,document,link']);
        $filePath = null;
        $fileSize = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('digital-resources', 'public');
            $filePath = $path;
            $fileSize = $request->file('file')->getSize();
        }
        DigitalResource::create([
            'title'       => $request->title,
            'type'        => $request->type,
            'subject_id'  => $request->subject_id ?: null,
            'class_id'    => $request->class_id ?: null,
            'description' => $request->description,
            'file_path'   => $filePath,
            'url'         => $request->url,
            'file_size'   => $fileSize,
            'is_premium'  => $request->boolean('is_premium'),
            'uploaded_by' => Auth::id(),
        ]);
        return back()->with('success', 'Resource added.');
    }

    public function viewDigital(int $id)
    {
        $resource = DigitalResource::findOrFail($id);
        $resource->increment('access_count');
        if ($resource->url) return redirect($resource->url);
        if ($resource->file_path) return redirect(Storage::url($resource->file_path));
        return back();
    }

    public function downloadDigital(int $id)
    {
        $resource = DigitalResource::findOrFail($id);
        return Storage::download($resource->file_path, $resource->title);
    }

    public function deleteDigital(int $id)
    {
        $resource = DigitalResource::findOrFail($id);
        if ($resource->file_path) Storage::delete($resource->file_path);
        $resource->delete();
        return back()->with('success', 'Resource deleted.');
    }

    public function stockRegister(Request $request)
    {
        $books = Book::orderBy('title')->paginate(25)->withQueryString();
        return view('library.stock', compact('books'));
    }

    public function exportStock()
    {
        return Excel::download(new StockRegisterExport(), 'stock-register.xlsx');
    }

    public function members(Request $request)
    {
        $tab = $request->get('tab', 'students');
        $members = null;
        $staff   = null;

        if ($tab === 'staff') {
            $staff = Employee::where('is_active', true)
                ->when($request->search, fn($q, $v) => $q->where(fn($q) =>
                    $q->where('first_name', 'like', "%$v%")
                      ->orWhere('last_name',  'like', "%$v%")
                      ->orWhere('employee_code', 'like', "%$v%")))
                ->orderBy('first_name')->paginate(25)->withQueryString();
        } else {
            $members = Student::with(['bookIssues' => fn($q) => $q->where('status', 'issued')])
                ->where('status', 'active')
                ->when($request->search, fn($q, $v) => $q->where(fn($q) =>
                    $q->where('first_name', 'like', "%$v%")
                      ->orWhere('last_name',  'like', "%$v%")
                      ->orWhere('admission_no', 'like', "%$v%")))
                ->when($request->status === 'suspended', fn($q) => $q->where('library_suspended', true))
                ->when($request->status === 'active',    fn($q) => $q->where('library_suspended', false))
                ->orderBy('first_name')->paginate(25)->withQueryString();
        }

        return view('library.members', compact('members', 'staff', 'tab'));
    }

    public function memberCard(int $id)
    {
        $student = Student::with('currentEnrollment.class')->findOrFail($id);
        return view('library.member-card', compact('student'));
    }

    public function memberHistory(int $id)
    {
        $student = Student::findOrFail($id);
        $issues  = BookIssue::with('book')->where('student_id', $id)->latest()->paginate(20);
        return view('library.member-history', compact('student', 'issues'));
    }

    public function renewBook(Request $request, int $id)
    {
        $issue = BookIssue::findOrFail($id);
        if ($issue->status !== 'issued') return back()->withErrors(['error' => 'Book is not currently issued.']);
        $request->validate(['due_date' => 'required|date|after:today']);
        $renewCount = ($issue->renew_count ?? 0) + 1;
        if ($renewCount > 3) return back()->withErrors(['error' => 'Maximum renewals (3) reached.']);
        $issue->update(['due_date' => $request->due_date, 'renew_count' => $renewCount]);
        return back()->with('success', 'Book renewed until ' . \Carbon\Carbon::parse($request->due_date)->format('d M Y') . '.');
    }

    public function currentlyIssued(Request $request)
    {
        $issues = BookIssue::with(['book', 'student'])
            ->where('status', 'issued')
            ->when($request->overdue_only, fn($q) => $q->where('due_date', '<', today()))
            ->when($request->search, fn($q, $v) => $q->whereHas('student', fn($q2) => $q2->where('first_name', 'like', "%$v%")->orWhere('last_name', 'like', "%$v%")))
            ->orderBy('due_date')->paginate(25)->withQueryString();
        $fineRate = LibrarySetting::forType('student')?->fine_per_day ?? 2;
        return view('library.currently-issued', compact('issues', 'fineRate'));
    }

    public function overdueReport(Request $request)
    {
        $overdueIssues = BookIssue::with(['book', 'student'])
            ->where('status', 'issued')
            ->where('due_date', '<', today())
            ->orderBy('due_date')->paginate(25)->withQueryString();
        return view('library.overdue-report', compact('overdueIssues'));
    }

    public function mostBorrowed(Request $request)
    {
        $period = $request->period ?? 30;
        $books  = Book::withCount(['issues as borrow_count' => fn($q) => $q->where('issue_date', '>=', now()->subDays($period))])
            ->orderByDesc('borrow_count')->paginate(20);
        return view('library.most-borrowed', compact('books', 'period'));
    }

    public function fineDefaulters(Request $request)
    {
        $defaulters = BookIssue::with(['student', 'book'])
            ->where('status', 'returned')
            ->where('fine_amount', '>', 0)
            ->where('fine_paid', false)
            ->orderByDesc('fine_amount')
            ->paginate(25);
        return view('library.fine-defaulters', compact('defaulters'));
    }

    public function markLost(Request $request, int $id)
    {
        $issue = BookIssue::with('book')->findOrFail($id);
        $issue->update(['status' => 'lost']);
        // Decrement available copies on the book
        $issue->book->decrement('available_copies');
        return back()->with('success', 'Book marked as lost. Replacement charges can be collected via fee module.');
    }

    // ── Library Settings (fine rates per member type) ────

    public function settings()
    {
        $student = LibrarySetting::forType('student');
        $staff   = LibrarySetting::forType('staff');
        return view('library.settings', compact('student', 'staff'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'types'                  => 'required|array',
            'types.*.fine_per_day'   => 'required|numeric|min:0',
            'types.*.loan_days'      => 'required|integer|min:1',
            'types.*.max_books'      => 'required|integer|min:1',
            'types.*.max_renewals'   => 'required|integer|min:0',
        ]);
        foreach ($request->types as $type => $data) {
            LibrarySetting::updateOrCreate(
                ['member_type' => $type],
                $data
            );
        }
        return back()->with('success', 'Library settings saved.');
    }

    // ── Fine outstanding per member ───────────────────────

    public function memberOutstanding(Request $request, int $id)
    {
        $student   = Student::findOrFail($id);
        $issues    = BookIssue::with('book')
            ->where('student_id', $id)
            ->where('fine_paid', false)
            ->where('fine_amount', '>', 0)
            ->get();
        $totalFine = $issues->sum('fine_amount');
        return view('library.member-outstanding', compact('student', 'issues', 'totalFine'));
    }

    // ── Book cover image upload ───────────────────────────

    public function uploadCover(Request $request, int $id)
    {
        $request->validate(['cover' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $book = Book::findOrFail($id);
        $path = $request->file('cover')->store('book-covers', 'public');
        $book->update(['cover_image' => $path]);
        return back()->with('success', 'Cover image updated.');
    }

    // ── Deaccession (write-off) a book ────────────────────

    public function deaccessionBook(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|string|max:300']);
        $book = Book::findOrFail($id);
        $book->update([
            'is_deaccessioned'  => true,
            'deaccession_date'  => now(),
            'deaccession_reason'=> $request->reason,
            'deaccession_by'    => auth()->id(),
            'is_available'      => false,
            'available_copies'  => 0,
        ]);
        return back()->with('success', '"' . $book->title . '" written off from library stock.');
    }

    // ── Bulk book import via Excel ────────────────────────

    public function bulkImportForm()
    {
        return view('library.books-bulk-import');
    }

    public function processBulkImport(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $import = new \App\Imports\BooksImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $count = $import->getRowCount();
        return back()->with('success', "$count books imported successfully.");
    }

    // ── Issue / Return receipt ────────────────────────────

    public function issueReceipt(int $id)
    {
        $issue  = BookIssue::with(['book', 'student', 'employee'])->findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        $pdf    = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.library-receipt', compact('issue', 'school'));
        $pdf->setPaper([0, 0, 226.77, 400], 'portrait'); // ~80mm thermal width
        return $pdf->stream('library-receipt-' . $id . '.pdf');
    }

    // ── Fine collection report ────────────────────────────

    public function fineReport(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();

        $issues = BookIssue::with(['book', 'student'])
            ->where('fine_amount', '>', 0)
            ->when($request->paid, fn($q, $v) => $v === 'yes' ? $q->where('fine_paid', true) : $q->where('fine_paid', false))
            ->whereBetween('due_date', [$from, $to])
            ->orderByDesc('due_date')
            ->get();

        $totalFine    = $issues->sum('fine_amount');
        $totalPaid    = $issues->where('fine_paid', true)->sum('fine_amount');
        $totalPending = $issues->where('fine_paid', false)->sum('fine_amount');

        return view('library.fine-report', compact('issues', 'from', 'to', 'totalFine', 'totalPaid', 'totalPending'));
    }

    // ── Annual stock audit report ─────────────────────────

    public function stockAudit(Request $request)
    {
        $year = $request->year ?? now()->year;

        $books = Book::with('issues')
            ->orderBy('title')
            ->get()
            ->map(function ($book) {
                return [
                    'book'             => $book,
                    'total_copies'     => $book->total_copies,
                    'available'        => $book->available_copies,
                    'issued'           => $book->total_copies - $book->available_copies,
                    'lost'             => $book->issues()->where('status', 'lost')->count(),
                    'deaccessioned'    => $book->is_deaccessioned,
                    'status'           => $book->is_deaccessioned ? 'Written Off' : ($book->available_copies > 0 ? 'Available' : 'All Issued'),
                ];
            });

        $totalBooks       = $books->count();
        $totalCopies      = $books->sum('total_copies');
        $totalAvailable   = $books->sum('available');
        $totalDeaccessioned = $books->where('deaccessioned', true)->count();

        return view('library.stock-audit', compact('books', 'year', 'totalBooks', 'totalCopies', 'totalAvailable', 'totalDeaccessioned'));
    }

    // ── Acquisition report ────────────────────────────────

    public function acquisitionReport(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $to   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();

        $books = Book::whereBetween('purchase_date', [$from, $to])
            ->orderBy('purchase_date')
            ->get();

        $totalCopies = $books->sum('total_copies');
        $totalValue  = $books->sum(fn($b) => $b->purchase_price * $b->total_copies);

        return view('library.acquisition-report', compact('books', 'from', 'to', 'totalCopies', 'totalValue'));
    }

    // ── Deaccession register ──────────────────────────────

    public function deaccessionRegister(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfYear();
        $to   = $request->to   ? \Carbon\Carbon::parse($request->to)   : now();

        $books = Book::where('is_deaccessioned', true)
            ->whereBetween('deaccession_date', [$from, $to])
            ->orderBy('deaccession_date')
            ->get();

        return view('library.deaccession-register', compact('books', 'from', 'to'));
    }

    // ── Book QR / Barcode Label Generation ───────────────

    public function bookQrLabel(int $id)
    {
        $book   = Book::findOrFail($id);
        $school = \App\Models\SchoolSetting::first();
        $qrData = $book->accession_number ?: ('BOOK-'.$book->id);
        $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(100)->generate($qrData);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.book-qr-label', compact('book', 'school', 'qr', 'qrData'))
            ->setPaper([0, 0, 180, 120], 'landscape');
        return $pdf->stream('book-label-'.$book->accession_number.'.pdf');
    }

    public function bulkQrLabels(Request $request)
    {
        $query = Book::where('is_active', true)->where('is_deaccessioned', false);
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->ids) {
            $ids = array_filter(explode(',', $request->ids));
            $query->whereIn('id', $ids);
        }
        $books  = $query->orderBy('accession_number')->limit(100)->get();
        $school = \App\Models\SchoolSetting::first();

        $booksWithQr = $books->map(function ($book) {
            $qrData = $book->accession_number ?: ('BOOK-'.$book->id);
            $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(80)->generate($qrData);
            return ['book' => $book, 'qr' => $qr, 'qrData' => $qrData];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.book-qr-labels-bulk', compact('booksWithQr', 'school'))
            ->setPaper('a4', 'portrait');
        return $pdf->download('book-qr-labels.pdf');
    }

    // ── Update storeBook/updateBook to handle cover_image ─

    private function handleCoverUpload(Request $request, Book $book): void
    {
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $book->update(['cover_image' => $path]);
        }
    }

    public function sendOverdueReminders()
    {
        $school = \App\Models\SchoolSetting::first();
        $overdueIssues = BookIssue::with(['book', 'student.user', 'student.parent', 'employee.user'])
            ->where('status', 'issued')
            ->where('due_date', '<', today())
            ->get()
            ->groupBy(fn($i) => $i->student_id ? 'student:'.$i->student_id : 'employee:'.$i->employee_id);

        $studentFineRate = LibrarySetting::forType('student')?->fine_per_day ?? 2;
        $staffFineRate   = LibrarySetting::forType('staff')?->fine_per_day ?? 2;
        $sent = 0; $skipped = 0;

        foreach ($overdueIssues as $groupKey => $issues) {
            [$type, $memberId] = explode(':', $groupKey);

            if ($type === 'student') {
                $student = $issues->first()->student;
                $email   = $student?->user?->email ?? $student?->parent?->email ?? null;
                $name    = $student?->full_name ?? 'Student';
                $fineRate = $studentFineRate;
            } else {
                $emp   = $issues->first()->employee;
                $email = $emp?->user?->email ?? null;
                $name  = $emp ? trim($emp->first_name . ' ' . $emp->last_name) : 'Staff';
                $fineRate = $staffFineRate;
            }

            if (!$email) { $skipped++; continue; }

            $books = $issues->map(fn($i) => [
                'title'    => $i->book?->title ?? 'Unknown',
                'due_date' => \Carbon\Carbon::parse($i->due_date)->format('d M Y'),
                'fine'     => today()->diffInDays($i->due_date) * $fineRate,
            ])->toArray();

            $totalFine = array_sum(array_column($books, 'fine'));

            try {
                Mail::to($email)->send(new LibraryOverdueMail(
                    memberName: $name,
                    overdueBooks: $books,
                    totalFine: $totalFine,
                    schoolName: $school?->school_name ?? config('app.name'),
                ));
                $sent++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return back()->with('success', "Overdue reminders sent: {$sent}. Skipped (no email): {$skipped}.");
    }

    public function suspendMember(Request $request, int $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        Student::findOrFail($id)->update([
            'library_suspended'        => true,
            'library_suspension_reason' => $request->reason,
        ]);
        return back()->with('success', 'Member library access suspended.');
    }

    public function unsuspendMember(int $id)
    {
        Student::findOrFail($id)->update([
            'library_suspended'        => false,
            'library_suspension_reason' => null,
        ]);
        return back()->with('success', 'Member library access restored.');
    }
}
