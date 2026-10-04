<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\FeeBulkAssignment;
use App\Models\FeeHead;
use App\Models\FeeInstallmentPlan;
use App\Models\FeePayment;
use App\Models\AuditLog;
use App\Models\FeeReminderConfig;
use App\Models\FeeStructure;
use App\Models\LateFeeRule;
use App\Models\Student;
use App\Models\StudentConcession;
use App\Models\StudentFeeCharge;
use App\Exports\FeeReportExport;
use App\Mail\FeeReminderMail;
use App\Models\FeePaymentSplit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class FeeController extends Controller
{
    public static function clearStatsCache(): void
    {
        try {
            Cache::forget('fee_overview_stats_v1_0_' . today()->toDateString());
            $years = AcademicYear::pluck('id');
            foreach ($years as $yId) {
                Cache::forget("fee_overview_stats_v1_{$yId}_" . today()->toDateString());
            }
        } catch (\Throwable $e) {}
    }

    public function index()
    {
        $currentYear = AcademicYear::current();
        $yearId = (int) ($currentYear?->id ?? 0);
        $todayStr = today()->toDateString();
        $currMonth = (int) now()->month;
        $currYear = (int) now()->year;

        $stats = Cache::remember("fee_overview_stats_v1_{$yearId}_{$todayStr}", 300, function () use ($currentYear, $yearId, $todayStr, $currMonth, $currYear) {
            $feeAgg = DB::table('fee_payments')
                ->where('is_cancelled', false)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN payment_date::date = '{$todayStr}' THEN total_paid ELSE 0 END), 0) as today_collection,
                    COALESCE(SUM(CASE WHEN EXTRACT(MONTH FROM payment_date) = {$currMonth} AND EXTRACT(YEAR FROM payment_date) = {$currYear} THEN total_paid ELSE 0 END), 0) as month_collection,
                    COALESCE(SUM(CASE WHEN academic_year_id = {$yearId} THEN total_paid ELSE 0 END), 0) as year_collection
                ")->first();

            $todayCollection = (float) ($feeAgg->today_collection ?? 0);
            $monthCollection = (float) ($feeAgg->month_collection ?? 0);
            $yearCollection  = (float) ($feeAgg->year_collection ?? 0);
            $yearDemand = StudentFeeCharge::when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->sum('amount');
            $outstanding = max(0, $yearDemand - $yearCollection);

            $defaultersCount = DB::table('students as s')
                ->join('student_fee_charges as sfc', 'sfc.student_id', '=', 's.id')
                ->leftJoin('fee_payments as fp', function ($j) {
                    $j->on('fp.student_id', '=', 's.id')->where('fp.is_cancelled', false);
                })
                ->where('s.status', 'active')
                ->when($currentYear, fn($q) => $q->where('sfc.academic_year_id', $currentYear->id))
                ->select('s.id')
                ->groupBy('s.id')
                ->havingRaw('SUM(sfc.amount) > COALESCE(SUM(fp.total_paid), 0)')
                ->get()->count();

            // Monthly collection trend (last 6 months)
            $monthlyTrend = FeePayment::where('is_cancelled', false)
                ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
                ->select(
                    DB::raw("TO_CHAR(payment_date, 'YYYY-MM') as month"),
                    DB::raw('SUM(total_paid) as total')
                )
                ->groupBy('month')->orderBy('month')->get();

            return compact('todayCollection', 'monthCollection', 'yearCollection', 'yearDemand', 'outstanding', 'defaultersCount', 'monthlyTrend');
        });

        $todayCollection = $stats['todayCollection'];
        $monthCollection = $stats['monthCollection'];
        $yearCollection  = $stats['yearCollection'];
        $yearDemand      = $stats['yearDemand'];
        $outstanding     = $stats['outstanding'];
        $defaultersCount = $stats['defaultersCount'];
        $monthlyTrend    = $stats['monthlyTrend'];

        $recentPayments = FeePayment::with(['student:id,first_name,last_name,admission_no', 'feeHead:id,name'])
            ->where('is_cancelled', false)
            ->latest('payment_date')->take(10)->get();

        return view('fees.index', compact(
            'currentYear', 'todayCollection', 'monthCollection',
            'yearCollection', 'yearDemand', 'outstanding',
            'defaultersCount', 'recentPayments', 'monthlyTrend'
        ));
    }

    public function structure()
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::activeCached();
        $feeHeads    = FeeHead::where('is_active', true)->get();
        $structures  = FeeStructure::with(['class', 'feeHead', 'academicYear'])
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get()->groupBy('class_id');

        return view('fees.structure', compact('classes', 'feeHeads', 'structures', 'currentYear'));
    }

    public function storeFeeHead(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'fee_type'       => 'required|string',
            'description'    => 'nullable|string',
            'gst_applicable' => 'nullable|boolean',
            'gst_percent'    => 'nullable|numeric|min:0|max:100',
            'hsn_code'       => 'nullable|string|max:20',
            'gst_type'       => 'nullable|in:inclusive,exclusive',
        ]);
        FeeHead::create([
            'name'           => $request->name,
            'fee_type'       => $request->fee_type,
            'description'    => $request->description,
            'is_active'      => true,
            'gst_applicable' => $request->boolean('gst_applicable'),
            'gst_percent'    => $request->gst_applicable ? $request->gst_percent : null,
            'hsn_code'       => $request->hsn_code,
            'gst_type'       => $request->gst_applicable ? ($request->gst_type ?: 'exclusive') : null,
        ]);
        return back()->with('success', 'Fee head created.');
    }

    public function collect(Request $request)
    {
        $student  = null;
        $dues     = collect();
        $classes  = Classes::active()->get();
        $feeHeads = FeeHead::where('is_active', true)->get();
        $currentYear = AcademicYear::current();

        // For autocomplete suggestions if needed
        $studentSuggestions = Student::where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        if ($request->student_id) {
            $q = trim($request->student_id);
            $student = Student::with([
                'currentEnrollment.class',
                'currentEnrollment.section',
                'currentEnrollment.academicYear',
                'feePayments' => fn($q) => $q->where('is_cancelled', false)->latest('payment_date'),
            ])->where(fn($sq) => $sq->where('id', is_numeric($q) ? (int)$q : 0)
                ->orWhere('admission_no', $q)
                ->orWhere(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%$q%"))
            ->first();

            if ($student && $currentYear) {
                // 1. Process terms from student admission fee schedule
                if (!empty($student->admission_fee_terms) && is_array($student->admission_fee_terms)) {
                    foreach ($student->admission_fee_terms as $idx => $term) {
                        $pending = (float)($term['pending'] ?? 0);
                        if ($pending > 0) {
                            $tNum = $term['term_number'] ?? ($idx + 1);
                            $tName = $term['name'] ?? ('Term ' . $tNum);
                            $dues->push((object)[
                                'id'            => 'term_' . $tNum,
                                'item_key'      => 'term_' . $tNum,
                                'item_type'     => 'term',
                                'term_number'   => $tNum,
                                'term_name'     => $tName,
                                'fee_head_id'   => null,
                                'name'          => $tName,
                                'display_label' => $tName . ' — ₹' . number_format($pending, 2) . ' due',
                                'balance'       => $pending,
                                'due_date'      => $term['due_date'] ?? null,
                            ]);
                        }
                    }
                } elseif ((float)($student->admission_pending_amount ?? 0) > 0) {
                    $pending = (float)$student->admission_pending_amount;
                    $dues->push((object)[
                        'id'            => 'term_1',
                        'item_key'      => 'term_1',
                        'item_type'     => 'term',
                        'term_number'   => 1,
                        'term_name'     => 'Term 1',
                        'fee_head_id'   => null,
                        'name'          => 'Term 1',
                        'display_label' => 'Term 1 — ₹' . number_format($pending, 2) . ' due',
                        'balance'       => $pending,
                        'due_date'      => null,
                    ]);
                }

                // 2. Process class fee structures
                $isNewAdmission = $currentYear->start_date
                    && $student->created_at->gte(\Carbon\Carbon::parse($currentYear->start_date)->startOfDay());
                $studentType = $isNewAdmission ? 'new_admission' : 'existing';

                $paid = FeePayment::where('student_id', $student->id)
                    ->where('academic_year_id', $currentYear->id)
                    ->where('is_cancelled', false)
                    ->whereNotNull('fee_head_id')
                    ->select('fee_head_id', DB::raw('SUM(total_paid) as paid'))
                    ->groupBy('fee_head_id')->get()->keyBy('fee_head_id');

                $structureDues = FeeStructure::with('feeHead')
                    ->where('class_id', $student->currentEnrollment?->class_id)
                    ->where('academic_year_id', $currentYear->id)
                    ->where(fn($q) => $q->where('applies_to', 'all')->orWhere('applies_to', $studentType))
                    ->get()->map(function ($s) use ($paid) {
                        $s->paid_amount = (float)($paid[$s->fee_head_id]->paid ?? 0);
                        $s->balance     = (float)($s->amount - $s->paid_amount);
                        return $s;
                    })->filter(fn($s) => $s->balance > 0)->map(function ($s) {
                        $name = $s->feeHead?->name ?? 'Fee';
                        return (object)[
                            'id'            => 'head_' . $s->fee_head_id,
                            'item_key'      => 'head_' . $s->fee_head_id,
                            'item_type'     => 'fee_head',
                            'term_number'   => null,
                            'term_name'     => null,
                            'fee_head_id'   => $s->fee_head_id,
                            'name'          => $name,
                            'display_label' => $name . ' — ₹' . number_format($s->balance, 2) . ' due',
                            'balance'       => $s->balance,
                            'due_date'      => $s->due_date,
                        ];
                    });

                $dues = $dues->concat($structureDues);

                // 3. Process individual student charges
                $hasAdmissionTerms = !empty($student->admission_fee_terms) || ((float)($student->total_admission_fee ?? 0) > 0);
                $studentCharges = StudentFeeCharge::with('feeHead')
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $currentYear->id)
                    ->where('is_active', true)
                    ->when($hasAdmissionTerms, fn($q) => $q->where('source', '!=', 'admission'))
                    ->whereNotIn('fee_head_id', $dues->pluck('fee_head_id')->filter())
                    ->get()->map(function ($c) use ($paid) {
                        $paidAmt = (float)($paid[$c->fee_head_id]->paid ?? 0);
                        $c->paid_amount = $paidAmt;
                        $c->balance     = (float)($c->amount - $paidAmt);
                        return $c;
                    })->filter(fn($c) => $c->balance > 0)->map(function ($c) {
                        $name = $c->feeHead?->name ?? $c->description ?? 'Fee Charge';
                        return (object)[
                            'id'            => 'charge_' . $c->id,
                            'item_key'      => 'charge_' . $c->id,
                            'item_type'     => 'charge',
                            'term_number'   => null,
                            'term_name'     => null,
                            'fee_head_id'   => $c->fee_head_id,
                            'name'          => $name,
                            'display_label' => $name . ' — ₹' . number_format($c->balance, 2) . ' due',
                            'balance'       => $c->balance,
                            'due_date'      => $c->due_date,
                        ];
                    });

                $dues = $dues->concat($studentCharges);
            }
        }

        return view('fees.collect', compact('student', 'dues', 'classes', 'feeHeads', 'currentYear', 'studentSuggestions'));
    }

    public function savePayment(Request $request)
    {
        $validated = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'fee_item_id'      => 'required|string',
            'amount'           => 'required|numeric|gt:0',
            'discount'         => 'nullable|numeric|min:0',
            'payment_date'     => 'required|date|before_or_equal:today',
            'payment_type'     => 'required|in:single,split',
            'payment_mode'     => 'required_if:payment_type,single|nullable|in:cash,cheque,dd,online,upi,card,bank_transfer',
            'payment_account'  => 'nullable|string|in:upi,cash_box_1,cash_box_2,bank',
            'transaction_id'   => 'nullable|string|max:100',
            'remarks'          => 'nullable|string|max:1000',
            'cheque_number'    => 'nullable|string|max:50',
            'cheque_bank'      => 'nullable|string|max:100',
            'cheque_branch'    => 'nullable|string|max:100',
            'cheque_date'      => 'nullable|date',
            'idempotency_token'=> 'nullable|string|max:100',
            'splits'           => 'required_if:payment_type,split|nullable|array',
            'splits.*.payment_mode'  => 'required_with:splits|string|in:cash,cheque,dd,online,upi,card,bank_transfer',
            'splits.*.payment_account' => 'nullable|string|in:upi,cash_box_1,cash_box_2,bank',
            'splits.*.amount'        => 'required_with:splits|numeric|gt:0',
            'splits.*.transaction_id'=> 'nullable|string|max:100',
            'splits.*.cheque_number' => 'nullable|string|max:50',
            'splits.*.cheque_date'   => 'nullable|date',
            'splits.*.bank_name'     => 'nullable|string|max:100',
            'splits.*.branch_name'   => 'nullable|string|max:100',
        ]);

        $amount = round((float) $request->input('amount'), 2);
        $discount = round((float) ($request->input('discount') ?? 0), 2);

        if ($discount < 0) {
            return back()->withErrors(['discount' => 'Discount cannot be negative.'])->withInput();
        }
        if ($discount >= $amount) {
            return back()->withErrors(['discount' => 'Discount cannot be greater than or equal to the payment amount.'])->withInput();
        }

        $netPayable = round($amount - $discount, 2);
        if ($netPayable <= 0) {
            return back()->withErrors(['amount' => 'Payable amount must be greater than zero.'])->withInput();
        }

        $isSplit = $request->input('payment_type') === 'split';
        $splits = $request->input('splits', []);

        if ($isSplit) {
            if (!is_array($splits) || count($splits) < 2) {
                return back()->withErrors(['splits' => 'Split payment requires at least two payment methods.'])->withInput();
            }
            $splitSum = 0;
            foreach ($splits as $sp) {
                $spAmount = (float)($sp['amount'] ?? 0);
                if ($spAmount <= 0) {
                    return back()->withErrors(['splits' => 'Payment amounts must be greater than zero.'])->withInput();
                }
                $splitSum += $spAmount;
            }
            $splitSum = round($splitSum, 2);
            if (abs($splitSum - $netPayable) > 0.01) {
                return back()->withErrors(['splits' => 'Payment amounts do not match the selected payment amount.'])->withInput();
            }
        }

        $currentYear = AcademicYear::current();

        // Idempotency / Double submission protection
        $idempotencyToken = $request->input('idempotency_token');
        if ($idempotencyToken) {
            $lockAcquired = Cache::add('fee_pay_lock_' . $idempotencyToken, true, 30);
            if (!$lockAcquired) {
                return back()->withErrors(['error' => 'A payment request is already being processed. Please refresh and check payment history.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $student = Student::where('id', $validated['student_id'])->lockForUpdate()->firstOrFail();

            $feeItemId = $validated['fee_item_id'];
            $feeHeadId = null;
            $termNumber = null;
            $termName = null;
            $maxAllowed = null;

            if (str_starts_with($feeItemId, 'term_')) {
                $termNumber = (int) substr($feeItemId, 5);
                $terms = $student->admission_fee_terms ?? [];
                $matchedTerm = null;
                foreach ($terms as $t) {
                    if (($t['term_number'] ?? null) == $termNumber) {
                        $matchedTerm = $t;
                        break;
                    }
                }
                if ($matchedTerm) {
                    $termName = $matchedTerm['name'] ?? ('Term ' . $termNumber);
                    $maxAllowed = (float)($matchedTerm['pending'] ?? 0);
                } else {
                    $termName = 'Term ' . $termNumber;
                    $maxAllowed = (float)($student->admission_pending_amount ?? 0);
                }
            } elseif (str_starts_with($feeItemId, 'head_')) {
                $feeHeadId = (int) substr($feeItemId, 5);
                $head = FeeHead::find($feeHeadId);
                $termName = $head?->name ?? 'Fee';
                $struct = FeeStructure::where('class_id', $student->currentEnrollment?->class_id)
                    ->where('academic_year_id', $currentYear?->id)
                    ->where('fee_head_id', $feeHeadId)
                    ->first();
                if ($struct) {
                    $paidSoFar = FeePayment::where('student_id', $student->id)
                        ->where('academic_year_id', $currentYear?->id)
                        ->where('fee_head_id', $feeHeadId)
                        ->where('is_cancelled', false)
                        ->sum('total_paid');
                    $maxAllowed = max(0, (float)$struct->amount - (float)$paidSoFar);
                }
            } elseif (str_starts_with($feeItemId, 'charge_')) {
                $chargeId = (int) substr($feeItemId, 7);
                $charge = StudentFeeCharge::find($chargeId);
                if ($charge) {
                    $feeHeadId = $charge->fee_head_id;
                    $termName = $charge->feeHead?->name ?? $charge->description ?? 'Fee Charge';
                    $paidSoFar = $charge->fee_head_id ? FeePayment::where('student_id', $student->id)
                        ->where('academic_year_id', $currentYear?->id)
                        ->where('fee_head_id', $charge->fee_head_id)
                        ->where('is_cancelled', false)
                        ->sum('total_paid') : 0;
                    $maxAllowed = max(0, (float)$charge->amount - (float)$paidSoFar);
                }
            }

            if ($maxAllowed !== null && $amount > ($maxAllowed + 0.01)) {
                DB::rollBack();
                return back()->withErrors(['amount' => 'Payment amount cannot exceed outstanding due of ₹' . number_format($maxAllowed, 2) . '.'])->withInput();
            }

            $paymentMode = $isSplit ? 'split' : $validated['payment_mode'];
            $paymentAccount = $validated['payment_account'] ?? null;
            if (!$paymentAccount && !$isSplit) {
                if ($paymentMode === 'cash') {
                    $paymentAccount = 'cash_box_1';
                } elseif (in_array($paymentMode, ['upi', 'online'])) {
                    $paymentAccount = 'upi';
                } else {
                    $paymentAccount = 'bank';
                }
            }

            $payment = FeePayment::create([
                'student_id'       => $student->id,
                'enrollment_id'    => $student->currentEnrollment?->id,
                'academic_year_id' => $currentYear?->id,
                'fee_head_id'      => $feeHeadId,
                'term_number'      => $termNumber,
                'term_name'        => $termName,
                'receipt_number'   => $this->generateReceiptNumber(),
                'payment_date'     => $validated['payment_date'],
                'amount'           => $amount,
                'late_fee'         => 0, // Requirement 8: Late fee not used
                'discount'         => $discount,
                'amount_paid'      => $netPayable,
                'total_paid'       => $netPayable,
                'payment_mode'     => $paymentMode,
                'payment_account'  => $paymentAccount,
                'transaction_id'   => $isSplit ? null : ($validated['transaction_id'] ?? null),
                'cheque_number'    => $isSplit ? null : ($validated['cheque_number'] ?? null),
                'cheque_date'      => $isSplit ? null : ($validated['cheque_date'] ?? null),
                'cheque_bank'      => $isSplit ? null : ($validated['cheque_bank'] ?? null),
                'cheque_branch'    => $isSplit ? null : ($validated['cheque_branch'] ?? null),
                'cheque_status'    => (!$isSplit && $paymentMode === 'cheque' && !empty($validated['cheque_number'])) ? 'pending' : null,
                'remarks'          => $validated['remarks'] ?? null,
                'collected_by'     => Auth::id(),
            ]);

            if ($isSplit) {
                foreach ($splits as $sp) {
                    $spMode = $sp['payment_mode'];
                    $spAccount = $sp['payment_account'] ?? null;
                    if (!$spAccount) {
                        if ($spMode === 'cash') {
                            $spAccount = 'cash_box_1';
                        } elseif (in_array($spMode, ['upi', 'online'])) {
                            $spAccount = 'upi';
                        } else {
                            $spAccount = 'bank';
                        }
                    }
                    FeePaymentSplit::create([
                        'fee_payment_id'  => $payment->id,
                        'payment_mode'    => $spMode,
                        'payment_account' => $spAccount,
                        'amount'          => (float)$sp['amount'],
                        'transaction_id'  => $sp['transaction_id'] ?? null,
                        'cheque_number'   => $sp['cheque_number'] ?? null,
                        'cheque_date'     => !empty($sp['cheque_date']) ? $sp['cheque_date'] : null,
                        'bank_name'       => $sp['bank_name'] ?? null,
                        'branch_name'     => $sp['branch_name'] ?? null,
                    ]);
                }
            }

            // Update student admission fee terms and pending amounts
            if ($termNumber !== null || str_starts_with($feeItemId, 'term_')) {
                $terms = $student->admission_fee_terms ?? [];
                $updatedTerms = [];
                $splitPaymentSummary = $isSplit
                    ? 'Split: ' . implode(', ', array_map(fn($s) => ucfirst($s['payment_mode']) . ' (₹' . number_format((float)$s['amount'], 2) . ')', $splits))
                    : ucfirst($paymentMode);

                if (is_array($terms) && count($terms) > 0) {
                    foreach ($terms as $t) {
                        if (($t['term_number'] ?? null) == $termNumber) {
                            $newPaid = round((float)($t['paid'] ?? 0) + $netPayable, 2);
                            $newPending = max(0, round((float)($t['amount'] ?? 0) - $newPaid, 2));
                            $newStatus = $newPending <= 0 ? 'paid' : ($newPaid > 0 ? 'partially_paid' : 'pending');
                            $t['paid'] = $newPaid;
                            $t['pending'] = $newPending;
                            $t['status'] = $newStatus;
                            $t['payment_mode'] = $splitPaymentSummary;
                            $t['payment_date'] = $validated['payment_date'];
                        }
                        $updatedTerms[] = $t;
                    }
                    $student->admission_fee_terms = $updatedTerms;
                }

                $student->admission_paid_amount = round((float)($student->admission_paid_amount ?? 0) + $netPayable, 2);
                $student->admission_pending_amount = max(0, round((float)($student->total_admission_fee ?? 0) - $student->admission_paid_amount, 2));
                $student->payment_status = $student->admission_pending_amount <= 0 ? 'paid' : 'partially_paid';
                $student->save();
            }

            DB::commit();

            AuditLog::record('fee_payment_collected', $payment, [], [
                'receipt_number' => $payment->receipt_number,
                'amount_paid'    => $payment->amount_paid,
                'payment_mode'   => $payment->payment_mode,
                'student_id'     => $student->id,
            ]);

            return redirect()->route('fees.receipt', $payment->id)
                ->with('success', 'Fee payment recorded successfully. Receipt #' . $payment->receipt_number);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Fee payment failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Unable to record payment: ' . $e->getMessage()])->withInput();
        }
    }

    public function defaulters(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $defaulters  = collect();

        if ($currentYear) {
            $query = Student::with('currentEnrollment.class')
                ->where('status', 'active')
                ->whereDoesntHave('feePayments', function ($q) use ($currentYear) {
                    $q->where('academic_year_id', $currentYear->id)->where('is_cancelled', false);
                });
            if ($request->class_id) {
                $query->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $request->class_id));
            }
            if ($request->blocked === '1') {
                $query->where('portal_blocked', true);
            } elseif ($request->blocked === '0') {
                $query->where('portal_blocked', false);
            }
            $defaulters = $query->paginate(25)->withQueryString();
        }

        return view('fees.defaulters', compact('classes', 'defaulters', 'currentYear'));
    }

    public function blockPortalAccess(Request $request, int $studentId)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $student = \App\Models\Student::findOrFail($studentId);
        $student->update([
            'portal_blocked'     => true,
            'portal_block_reason'=> $request->reason ?: 'Long-term fee defaulter',
            'portal_blocked_at'  => now(),
        ]);
        return back()->with('success', "Portal access blocked for {$student->full_name}.");
    }

    public function unblockPortalAccess(int $studentId)
    {
        $student = \App\Models\Student::findOrFail($studentId);
        $student->update([
            'portal_blocked'     => false,
            'portal_block_reason'=> null,
            'portal_blocked_at'  => null,
        ]);
        return back()->with('success', "Portal access restored for {$student->full_name}.");
    }

    public function receipt(int $id)
    {
        $payment = FeePayment::with(['student.currentEnrollment.class', 'feeHead', 'collectedBy', 'splits'])->findOrFail($id);
        return view('fees.receipt', compact('payment'));
    }

    public function report(Request $request)
    {
        $classes     = Classes::active()->get();
        $feeHeads    = FeeHead::where('is_active', true)->get();
        $currentYear = AcademicYear::current();
        $payments    = FeePayment::with(['student', 'feeHead'])
            ->where('is_cancelled', false)
            ->when($request->from_date, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($request->to_date,   fn($q, $v) => $q->whereDate('payment_date', '<=', $v))
            ->when($request->fee_head_id, fn($q, $v) => $q->where('fee_head_id', $v))
            ->latest('payment_date')->paginate(25);

        $total = $payments->sum('total_paid');

        return view('fees.report', compact('payments', 'total', 'classes', 'feeHeads'));
    }

    private function generateReceiptNumber(): string
    {
        $prefix = 'RCP-' . date('Ym') . '-';
        $last   = FeePayment::where('receipt_number', 'like', $prefix . '%')->max('receipt_number');
        $seq    = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function saveStructure(Request $request)
    {
        $request->validate(['class_id' => 'required|exists:classes,id', 'fee_head_id' => 'required|exists:fee_heads,id', 'amount' => 'required|numeric|min:0']);
        $currentYear = AcademicYear::current();
        FeeStructure::updateOrCreate(
            ['class_id' => $request->class_id, 'fee_head_id' => $request->fee_head_id, 'academic_year_id' => $currentYear?->id],
            ['amount' => $request->amount, 'due_date' => $request->due_date, 'applies_to' => $request->applies_to ?? 'all']
        );
        return back()->with('success', 'Fee structure saved.');
    }

    public function reportExport(Request $request)
    {
        return Excel::download(new FeeReportExport($request->from_date, $request->to_date), 'fee-report.xlsx');
    }

    public function concessions(Request $request)
    {
        $classes     = Classes::active()->get();
        $feeHeads    = FeeHead::where('is_active', true)->get();
        $concessions = StudentConcession::with(['student', 'scheme'])->latest()->paginate(20)->withQueryString();
        return view('fees.concessions', compact('classes', 'feeHeads', 'concessions'));
    }

    public function saveConcession(Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id', 'concession_type' => 'required|string', 'value' => 'required|numeric|min:0']);
        StudentConcession::create(array_merge($request->only(['student_id', 'concession_type', 'value_type', 'value', 'valid_from', 'valid_to', 'remarks', 'scholarship_scheme_id']), ['granted_by' => Auth::id()]));
        return back()->with('success', 'Concession granted.');
    }

    public function deleteConcession(int $id)
    {
        StudentConcession::findOrFail($id)->delete();
        return back()->with('success', 'Concession removed.');
    }

    public function scholarshipRenewalReminders(Request $request)
    {
        $daysAhead = max(1, (int) ($request->days ?? 30));
        $cutoff    = now()->addDays($daysAhead)->toDateString();

        $expiring = StudentConcession::with(['student', 'scheme'])
            ->whereNotNull('valid_to')
            ->whereDate('valid_to', '<=', $cutoff)
            ->whereDate('valid_to', '>=', today())
            ->where('status', 'active')
            ->orderBy('valid_to')
            ->get();

        $expired = StudentConcession::with(['student', 'scheme'])
            ->whereNotNull('valid_to')
            ->whereDate('valid_to', '<', today())
            ->where('status', 'active')
            ->orderBy('valid_to', 'desc')
            ->take(50)->get();

        return view('fees.scholarship-renewals', compact('expiring', 'expired', 'daysAhead'));
    }

    public function lateFeeRules()
    {
        $feeHeads = FeeHead::where('is_active', true)->get();
        $rules    = LateFeeRule::orderBy('name')->get();
        return view('fees.late-fee-rules', compact('feeHeads', 'rules'));
    }

    public function storeLateFee(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100', 'type' => 'required|in:flat,per_day,percentage', 'amount' => 'required|numeric|min:0']);
        LateFeeRule::create($request->only(['name', 'type', 'amount', 'grace_days', 'max_amount', 'is_active']));
        return back()->with('success', 'Late fee rule added.');
    }

    public function deleteLateFee(int $id)
    {
        LateFeeRule::findOrFail($id)->delete();
        return back()->with('success', 'Rule deleted.');
    }

    public function installmentPlans()
    {
        $classes       = Classes::active()->get();
        $lateFeeRules  = LateFeeRule::where('is_active', true)->get();
        $plans         = FeeInstallmentPlan::with(['installments', 'class', 'lateFeeRule'])->latest()->get();
        return view('fees.installment-plans', compact('classes', 'lateFeeRules', 'plans'));
    }

    public function storeInstallmentPlan(Request $request)
    {
        if ($request->filled('plan_id')) {
            $request->validate(['amount' => 'required|numeric|min:0']);
            $plan = FeeInstallmentPlan::findOrFail($request->plan_id);
            $plan->installments()->create($request->only([
                'installment_number', 'name', 'amount', 'due_date', 'fee_head_id',
            ]));
            return back()->with('success', 'Installment added.');
        }

        $request->validate([
            'name'       => 'required|string|max:100',
            'frequency'  => 'required|in:one_time,monthly,quarterly,half_yearly,annually',
            'start_date' => 'required|date',
            'count'      => 'required|integer|min:1|max:24',
        ]);

        $plan = FeeInstallmentPlan::create([
            'name'             => $request->name,
            'class_id'         => $request->class_id ?: null,
            'academic_year_id' => $request->academic_year_id ?: null,
            'late_fee_rule_id' => $request->late_fee_rule_id ?: null,
            'frequency'        => $request->frequency,
            'start_date'       => $request->start_date,
            'installments_count' => $request->count,
            'description'      => $request->description,
            'is_active'        => true,
        ]);

        // Auto-generate installment due dates based on frequency
        $start = \Carbon\Carbon::parse($request->start_date);
        $intervalMap = [
            'one_time'   => null,
            'monthly'    => ['months', 1],
            'quarterly'  => ['months', 3],
            'half_yearly'=> ['months', 6],
            'annually'   => ['years', 1],
        ];
        $interval = $intervalMap[$request->frequency];
        $labels = [
            'one_time'   => ['Full Payment'],
            'monthly'    => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'quarterly'  => ['Q1','Q2','Q3','Q4'],
            'half_yearly'=> ['Half-Yearly 1','Half-Yearly 2'],
            'annually'   => ['Annual Payment'],
        ];
        $labelSet = $labels[$request->frequency] ?? [];

        for ($i = 0; $i < $request->count; $i++) {
            $dueDate = $interval
                ? $start->copy()->add($interval[0], $i * $interval[1])
                : $start->copy();
            $plan->installments()->create([
                'installment_number' => $i + 1,
                'name'               => $labelSet[$i % count($labelSet)] ?? 'Installment ' . ($i + 1),
                'due_date'           => $dueDate->format('Y-m-d'),
                'amount'             => 0,
                'amount_percentage'  => round(100 / $request->count, 2),
            ]);
        }

        return redirect()->route('fees.installments.edit', $plan->id)
            ->with('success', "Plan \"{$plan->name}\" created with {$request->count} installments. Set the amounts below.");
    }

    public function showInstallmentPlan(int $id)
    {
        $plan = FeeInstallmentPlan::with('installments')->findOrFail($id);
        return view('fees.installment-show', compact('plan'));
    }

    public function editInstallmentPlan(int $id)
    {
        $plan = FeeInstallmentPlan::with('installments')->findOrFail($id);
        $feeHeads = FeeHead::where('is_active', true)->get();
        return view('fees.installment-edit', compact('plan', 'feeHeads'));
    }

    public function daybook(Request $request)
    {
        $feeHeads = FeeHead::where('is_active', true)->get();
        $date = $request->input('date', today()->toDateString());
        $payments = FeePayment::with(['enrollment.student', 'enrollment.class', 'feeHead', 'collectedBy'])
            ->whereDate('payment_date', $date)
            ->when($request->fee_head_id, fn($q, $v) => $q->where('fee_head_id', $v))
            ->orderBy('created_at')->get();
        return view('fees.daybook', compact('feeHeads', 'payments'));
    }

    public function daybookPdf(Request $request)
    {
        $feeHeads = FeeHead::where('is_active', true)->get();
        $payments = FeePayment::with(['enrollment.student', 'enrollment.class', 'feeHead'])
            ->whereDate('payment_date', $request->date ?? today())->get();
        $pdf = Pdf::loadView('pdf.daybook', compact('payments', 'feeHeads'));
        return $pdf->download('daybook-' . ($request->date ?? today()->toDateString()) . '.pdf');
    }

    public function ledger(Request $request)
    {
        $classes  = Classes::active()->get();
        $students = collect();
        $enrollment = null;
        $ledger = collect();
        $totalFees = $totalPaid = $totalConcession = $balance = 0;

        if ($request->class_id) {
            $currentYear = AcademicYear::current();
            $students = Student::whereHas('enrollments', fn($q) => $q->where('class_id', $request->class_id)->where('status', 'active'))->orderBy('first_name')->get();
        }
        if ($request->student_id) {
            $currentYear = AcademicYear::current();
            $enrollment  = \App\Models\StudentEnrollment::with(['student', 'class', 'section'])->where('student_id', $request->student_id)->where('status', 'active')->first();
            if ($enrollment) {
                $structures = FeeStructure::with('feeHead')->where('class_id', $enrollment->class_id)->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->get();
                $payments   = FeePayment::with('feeHead')->where('student_id', $request->student_id)->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))->orderBy('payment_date')->get();
                $totalFees  = $structures->sum('amount');
                $totalPaid  = $payments->where('is_cancelled', false)->sum('total_paid');
                $totalConcession = 0;
                $balance    = $totalFees - $totalPaid - $totalConcession;
                $ledger     = $payments->map(fn($p) => ['date' => $p->payment_date, 'receipt' => $p->receipt_number, 'fee_head' => $p->feeHead?->name, 'charged' => 0, 'paid' => $p->total_paid, 'late_fee' => $p->late_fee, 'discount' => $p->discount, 'balance' => 0, 'mode' => $p->payment_mode, 'status' => $p->is_cancelled ? 'cancelled' : 'paid', 'type' => 'payment']);
            }
        }
        return view('fees.ledger', compact('classes', 'students', 'enrollment', 'ledger', 'totalFees', 'totalPaid', 'totalConcession', 'balance'));
    }

    public function ledgerPdf(Request $request)
    {
        return redirect()->route('fees.ledger', $request->query());
    }

    public function cancellation(Request $request)
    {
        $payment = null;
        if ($request->filled('receipt')) {
            $payment = FeePayment::with(['enrollment.student', 'enrollment.class', 'feeHead', 'collectedBy'])->where('receipt_number', $request->receipt)->first();
        }
        $recentCancellations = FeePayment::with(['enrollment.student', 'feeHead'])->where('is_cancelled', true)->latest('updated_at')->take(15)->get();
        return view('fees.cancellation', compact('payment', 'recentCancellations'));
    }

    public function cancelReceipt(Request $request, int $id)
    {
        $request->validate(['cancel_reason' => 'required|string|min:5']);
        FeePayment::findOrFail($id)->update(['is_cancelled' => true, 'cancel_reason' => $request->cancel_reason, 'cancelled_by' => Auth::id(), 'cancelled_at' => now()]);
        return back()->with('success', 'Receipt cancelled successfully.');
    }

    public function tallyExport()
    {
        $exportLogs = collect();
        $feeHeads   = FeeHead::orderBy('name')->get();
        return view('fees.tally-export', compact('exportLogs', 'feeHeads'));
    }

    public function saveTallyLedgerMap(Request $request)
    {
        foreach ((array)$request->ledger_names as $headId => $ledgerName) {
            FeeHead::where('id', $headId)->update(['tally_ledger_name' => trim($ledgerName) ?: null]);
        }
        return back()->with('success', 'Tally ledger mapping saved.');
    }

    public function defaulterAging(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $agingBands  = ['0-30' => [], '31-60' => [], '61-90' => [], '90+' => []];
        $defaulterList = collect();

        if ($currentYear) {
            $students = Student::with(['currentEnrollment.class'])
                ->where('status', 'active')
                ->when($request->class_id, fn($q, $v) => $q->whereHas('currentEnrollment', fn($q2) => $q2->where('class_id', $v)))
                ->get();

            foreach ($students as $student) {
                $totalFee  = FeeStructure::where('class_id', $student->currentEnrollment?->class_id)->where('academic_year_id', $currentYear->id)->sum('amount');
                $totalPaid = FeePayment::where('student_id', $student->id)->where('academic_year_id', $currentYear->id)->where('is_cancelled', false)->sum('total_paid');
                $balance   = $totalFee - $totalPaid;
                if ($balance <= 0) continue;
                $lastPayment = FeePayment::where('student_id', $student->id)->where('is_cancelled', false)->latest('payment_date')->first();
                $daysSince   = $lastPayment ? now()->diffInDays($lastPayment->payment_date) : 365;
                $defaulterList->push(compact('student', 'totalFee', 'totalPaid', 'balance', 'daysSince'));
            }

            foreach ($defaulterList as $d) {
                if ($d['daysSince'] <= 30) $agingBands['0-30'][] = $d;
                elseif ($d['daysSince'] <= 60) $agingBands['31-60'][] = $d;
                elseif ($d['daysSince'] <= 90) $agingBands['61-90'][] = $d;
                else $agingBands['90+'][] = $d;
            }
        }

        return view('fees.defaulter-aging', compact('classes', 'currentYear', 'defaulterList', 'agingBands'));
    }

    public function demandNotice(int $id)
    {
        $student     = Student::with(['currentEnrollment.class'])->findOrFail($id);
        $currentYear = AcademicYear::current();
        $feeStructures = FeeStructure::with('feeHead')->where('class_id', $student->currentEnrollment?->class_id)->where('academic_year_id', $currentYear?->id)->get();
        $payments      = FeePayment::with('feeHead')->where('student_id', $student->id)->where('academic_year_id', $currentYear?->id)->where('is_cancelled', false)->get();
        $paid = $payments->groupBy('fee_head_id')->map(fn($g) => $g->sum('total_paid'));
        $dues = $feeStructures->map(fn($s) => ['feeHead' => $s->feeHead, 'amount' => $s->amount, 'paid' => $paid[$s->fee_head_id] ?? 0, 'balance' => $s->amount - ($paid[$s->fee_head_id] ?? 0)])->where('balance', '>', 0);
        $school = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.demand-notice', compact('student', 'dues', 'school', 'currentYear'));
        return $pdf->download('demand-notice-' . $student->id . '.pdf');
    }

    public function duplicateReceipt(int $id)
    {
        $payment = FeePayment::with(['student.currentEnrollment.class', 'feeHead', 'collectedBy', 'splits'])->findOrFail($id);
        $school  = \App\Models\SchoolSetting::first();
        $pdf = Pdf::loadView('pdf.receipt', compact('payment', 'school'));
        return $pdf->stream('duplicate-receipt-' . $payment->receipt_number . '.pdf');
    }

    public function monthlyCollection(Request $request)
    {
        $currentYear = AcademicYear::current();
        // Default start year: April of the academic year's start calendar year
        $defaultStartYear = $currentYear
            ? (int) \Carbon\Carbon::parse($currentYear->start_date ?? now())->format('Y')
            : (now()->month >= 4 ? now()->year : now()->year - 1);
        $startYear = (int) $request->input('start_year', $defaultStartYear);

        $months = [];
        $data   = [];
        $total  = 0;
        // April–December of startYear
        for ($m = 4; $m <= 12; $m++) {
            $sum = FeePayment::whereYear('payment_date', $startYear)->whereMonth('payment_date', $m)->where('is_cancelled', false)->sum('total_paid');
            $months[] = ['year' => $startYear, 'month' => $m, 'label' => date('M', mktime(0, 0, 0, $m, 1)) . ' ' . $startYear];
            $data[]   = (float) $sum;
            $total   += $sum;
        }
        // January–March of startYear+1
        for ($m = 1; $m <= 3; $m++) {
            $sum = FeePayment::whereYear('payment_date', $startYear + 1)->whereMonth('payment_date', $m)->where('is_cancelled', false)->sum('total_paid');
            $months[] = ['year' => $startYear + 1, 'month' => $m, 'label' => date('M', mktime(0, 0, 0, $m, 1)) . ' ' . ($startYear + 1)];
            $data[]   = (float) $sum;
            $total   += $sum;
        }

        $feeHeads = FeeHead::where('is_active', true)->get();
        return view('fees.monthly-collection', compact('months', 'data', 'total', 'startYear', 'feeHeads', 'currentYear'));
    }

    public function annualCollection(Request $request)
    {
        $years = FeePayment::selectRaw('EXTRACT(YEAR FROM payment_date)::int as yr')->distinct()->orderByDesc('yr')->pluck('yr');
        $data  = [];
        foreach ($years as $yr) {
            $data[$yr] = FeePayment::whereYear('payment_date', $yr)->where('is_cancelled', false)->sum('total_paid');
        }
        return view('fees.annual-collection', compact('data', 'years'));
    }

    public function outstandingBalance(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $outstanding = collect();

        if ($currentYear) {
            $students = Student::with(['currentEnrollment.class'])
                ->where('status', 'active')
                ->when($request->class_id, fn($q, $v) => $q->whereHas('currentEnrollment', fn($q2) => $q2->where('class_id', $v)))
                ->get();

            foreach ($students as $student) {
                $totalFee  = FeeStructure::where('class_id', $student->currentEnrollment?->class_id)->where('academic_year_id', $currentYear->id)->sum('amount');
                $totalPaid = FeePayment::where('student_id', $student->id)->where('academic_year_id', $currentYear->id)->where('is_cancelled', false)->sum('total_paid');
                $balance   = $totalFee - $totalPaid;
                if ($balance > 0) $outstanding->push(['student' => $student, 'totalFee' => $totalFee, 'totalPaid' => $totalPaid, 'balance' => $balance]);
            }
            $outstanding = $outstanding->sortByDesc('balance');
        }

        return view('fees.outstanding', compact('classes', 'currentYear', 'outstanding'));
    }

    public function tallyDownload(Request $request)
    {
        $request->validate(['from_date' => 'required|date', 'to_date' => 'required|date|after_or_equal:from_date']);
        $payments = FeePayment::with(['enrollment.student', 'feeHead'])
            ->where('is_cancelled', false)
            ->whereDate('payment_date', '>=', $request->from_date)
            ->whereDate('payment_date', '<=', $request->to_date)
            ->get();
        if ($request->format === 'csv') {
            return Excel::download(new FeeReportExport($request->from_date, $request->to_date), 'tally-export.csv', \Maatwebsite\Excel\Excel::CSV);
        }
        $school = \App\Models\SchoolSetting::first();
        // Tally XML with GST details
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<ENVELOPE><HEADER><TALLYREQUEST>Import Data</TALLYREQUEST></HEADER><BODY><IMPORTDATA>';
        $xml .= '<REQUESTDESC><REPORTNAME>Vouchers</REPORTNAME><STATICVARIABLES><SVCURRENTCOMPANY>' . htmlspecialchars($school?->school_name ?? 'School') . '</SVCURRENTCOMPANY></STATICVARIABLES></REQUESTDESC>';
        $xml .= '<REQUESTDATA>';
        foreach ($payments as $p) {
            $feeHead  = $p->feeHead;
            $student  = $p->student;
            $date     = date('Ymd', strtotime($p->payment_date));
            $narr     = htmlspecialchars('Fee: ' . ($feeHead?->name ?? '') . ' | ' . ($student?->full_name ?? '') . ' | Rcpt: ' . $p->receipt_number);
            $gstRate  = (float)($feeHead?->gst_percent ?? 0);
            $gstAmt   = $gstRate > 0 ? round($p->total_paid - ($p->total_paid / (1 + $gstRate / 100)), 2) : 0;
            $baseAmt  = $p->total_paid - $gstAmt;
            $hsnCode  = $feeHead?->hsn_code ?? '';
            $ledger   = htmlspecialchars($feeHead?->tally_ledger_name ?: $feeHead?->name ?: 'Fees Received');
            $xml .= '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
            $xml .= '<VOUCHER REMOTEID="' . $p->id . '" VCHTYPE="Receipt" ACTION="Create">';
            $xml .= '<DATE>' . $date . '</DATE>';
            $xml .= '<NARRATION>' . $narr . '</NARRATION>';
            $xml .= '<VOUCHERTYPENAME>Receipt</VOUCHERTYPENAME>';
            $xml .= '<VOUCHERNUMBER>' . htmlspecialchars($p->receipt_number) . '</VOUCHERNUMBER>';
            // Dr: Cash / Bank
            $xml .= '<ALLLEDGERENTRIES.LIST><LEDGERNAME>' . ucfirst($p->payment_mode ?? 'Cash') . '</LEDGERNAME><ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE><AMOUNT>-' . $p->total_paid . '</AMOUNT></ALLLEDGERENTRIES.LIST>';
            // Cr: Fee Ledger (base amount)
            $xml .= '<ALLLEDGERENTRIES.LIST><LEDGERNAME>' . $ledger . '</LEDGERNAME><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>' . $baseAmt . '</AMOUNT>';
            if ($gstRate > 0) {
                $xml .= '<CATEGORYALLOCATIONS.LIST><CATEGORY>Not Applicable</CATEGORY><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>' . $baseAmt . '</AMOUNT></CATEGORYALLOCATIONS.LIST>';
            }
            $xml .= '</ALLLEDGERENTRIES.LIST>';
            // Cr: GST ledger if applicable
            if ($gstAmt > 0) {
                $xml .= '<ALLLEDGERENTRIES.LIST><LEDGERNAME>GST Payable</LEDGERNAME><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>' . $gstAmt . '</AMOUNT>';
                $xml .= '<GSTDETAILS.LIST><GSTRATEINDEX>' . $gstRate . '</GSTRATEINDEX><HSNCODE>' . htmlspecialchars($hsnCode) . '</HSNCODE><TAXABILITY>Taxable</TAXABILITY><TAXTYPE>GST</TAXTYPE><IGSTRATE>' . $gstRate . '</IGSTRATE><CGSTRATE>0</CGSTRATE><SGSTRATE>0</SGSTRATE></GSTDETAILS.LIST>';
                $xml .= '</ALLLEDGERENTRIES.LIST>';
            }
            $xml .= '</VOUCHER></TALLYMESSAGE>';
        }
        $xml .= '</REQUESTDATA></IMPORTDATA></BODY></ENVELOPE>';
        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="tally-export-' . $request->from_date . '-to-' . $request->to_date . '.xml"');
    }

    public function exportDefaulters(Request $request)
    {
        $currentYear = \App\Models\AcademicYear::current();
        $defaulters = \App\Models\Student::with(['currentEnrollment.class'])
            ->whereHas('currentEnrollment')
            ->get()
            ->map(function ($student) use ($currentYear) {
                $classId = $student->currentEnrollment?->class_id;
                $total = FeeStructure::where('class_id', $classId)
                    ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                    ->sum('amount');
                $paid = FeePayment::where('student_id', $student->id)->where('is_cancelled', false)->sum('total_paid');
                $balance = $total - $paid;
                if ($balance <= 0) return null;
                return [
                    'Admission No'  => $student->admission_number,
                    'Student Name'  => $student->full_name,
                    'Class'         => $student->currentEnrollment?->class?->name,
                    'Total Fee'     => $total,
                    'Paid'          => $paid,
                    'Balance'       => $balance,
                    'Parent Mobile' => $student->father_mobile ?? $student->mother_mobile,
                ];
            })->filter()->values();

        return Excel::download(new class($defaulters) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private $rows) {}
            public function collection() { return $this->rows; }
            public function headings(): array { return array_keys($this->rows->first() ?? []); }
        }, 'fee-defaulters.xlsx');
    }

    public function classWiseCollection(Request $request)
    {
        $year  = \App\Models\AcademicYear::current();
        $classes = \App\Models\Classes::orderBy('sort_order')->get();
        $data  = [];
        foreach ($classes as $class) {
            $studentIds = \App\Models\StudentEnrollment::where('class_id', $class->id)
                ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
                ->pluck('student_id');
            $collected = FeePayment::whereIn('student_id', $studentIds)->where('is_cancelled', false)
                ->when($request->from_date, fn($q) => $q->whereDate('payment_date', '>=', $request->from_date))
                ->when($request->to_date, fn($q) => $q->whereDate('payment_date', '<=', $request->to_date))
                ->sum('total_paid');
            $expected = FeeStructure::where('class_id', $class->id)
                ->when($year, fn($q) => $q->where('academic_year_id', $year->id))
                ->sum('amount') * $studentIds->count();
            $data[] = ['class' => $class, 'expected' => $expected, 'collected' => $collected, 'outstanding' => max(0, $expected - $collected), 'student_count' => $studentIds->count()];
        }
        return view('fees.class-wise-collection', compact('data', 'year'));
    }

    public function feeHeadCollection(Request $request)
    {
        $year  = \App\Models\AcademicYear::current();
        $heads = FeeHead::orderBy('name')->get();
        $data  = [];
        foreach ($heads as $head) {
            $collected = FeePayment::where('fee_head_id', $head->id)->where('is_cancelled', false)
                ->when($request->from_date, fn($q) => $q->whereDate('payment_date', '>=', $request->from_date))
                ->when($request->to_date, fn($q) => $q->whereDate('payment_date', '<=', $request->to_date))
                ->sum('total_paid');
            $data[] = ['head' => $head, 'collected' => $collected];
        }
        $grandTotal = array_sum(array_column($data, 'collected'));
        return view('fees.fee-head-collection', compact('data', 'grandTotal'));
    }

    // ── Bulk Fee Assignment ─────────────────────────────────

    public function bulkAssign(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $structures  = FeeStructure::with('head')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->get()->groupBy('class_id');
        return view('fees.bulk-assign', compact('classes', 'structures', 'currentYear'));
    }

    public function processBulkAssign(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);
        $currentYear = AcademicYear::current();
        $classId     = $request->class_id;

        $structures = FeeStructure::where('class_id', $classId)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->with('head')->get();

        if ($structures->isEmpty()) {
            return back()->with('error', 'No fee structure defined for this class in the current academic year.');
        }

        $enrollments = \App\Models\StudentEnrollment::where('class_id', $classId)
            ->where('status', 'active')
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->pluck('student_id');

        $assigned = 0;
        foreach ($enrollments as $studentId) {
            foreach ($structures as $structure) {
                $alreadyHas = FeePayment::where('student_id', $studentId)
                    ->where('fee_head_id', $structure->fee_head_id)
                    ->where('academic_year_id', $currentYear?->id)
                    ->exists();
                if (!$alreadyHas) $assigned++;
            }
        }

        \App\Models\FeeBulkAssignment::create([
            'class_id'        => $classId,
            'academic_year_id'=> $currentYear?->id,
            'assigned_by'     => Auth::id(),
            'student_count'   => $enrollments->count(),
        ]);

        return back()->with('success', "Fee structure bulk-assigned to {$enrollments->count()} students in class. Pending entries: {$assigned}.");
    }

    // ── Fee Revision ────────────────────────────────────────

    public function feeRevision(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();
        $heads       = FeeHead::where('is_active', true)->orderBy('name')->get();
        $structures  = collect();

        if ($request->class_id) {
            $structures = FeeStructure::where('class_id', $request->class_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->with('head')->get();
        }

        return view('fees.revision', compact('classes', 'heads', 'structures', 'currentYear'));
    }

    public function saveFeeRevision(Request $request)
    {
        $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'amounts'     => 'required|array',
            'amounts.*'   => 'numeric|min:0',
        ]);
        $currentYear = AcademicYear::current();
        $updated = 0;
        foreach ($request->amounts as $structureId => $amount) {
            $structure = FeeStructure::where('id', $structureId)
                ->where('class_id', $request->class_id)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->first();
            if ($structure && $structure->amount != $amount) {
                \App\Models\FeeChangeLog::create([
                    'class_id'        => $request->class_id,
                    'fee_head_id'     => $structure->fee_head_id,
                    'academic_year_id'=> $currentYear?->id,
                    'change_type'     => 'revision',
                    'old_amount'      => $structure->amount,
                    'new_amount'      => $amount,
                    'reason'          => $request->reason ?? 'Bulk fee revision',
                    'changed_by'      => Auth::id(),
                ]);
                $structure->update(['amount' => $amount]);
                $updated++;
            }
        }
        return back()->with('success', "{$updated} fee line(s) revised.");
    }

    // ── Cheque Bounce Handling ────────────────────────────

    public function chequePending(Request $request)
    {
        $payments = FeePayment::with(['student', 'feeHead'])
            ->where('payment_mode', 'cheque')
            ->where('cheque_status', 'pending')
            ->orderBy('cheque_date')
            ->get();
        return view('fees.cheque-pending', compact('payments'));
    }

    public function markChequeCleared(int $id)
    {
        FeePayment::findOrFail($id)->update(['cheque_status' => 'cleared']);
        return back()->with('success', 'Cheque marked as cleared.');
    }

    public function markChequeBounced(Request $request, int $id)
    {
        $request->validate(['bounce_reason' => 'nullable|string|max:255', 'bounce_charge' => 'nullable|numeric|min:0']);
        $payment = FeePayment::findOrFail($id);

        // Reverse original payment by cancelling it
        $payment->update([
            'cheque_status' => 'bounced',
            'bounce_reason' => $request->bounce_reason,
            'bounce_charge' => $request->bounce_charge ?? 0,
            'is_cancelled'  => true,
            'cancel_reason' => 'Cheque bounced: ' . $request->bounce_reason,
            'cancelled_by'  => auth()->id(),
            'cancelled_at'  => now(),
        ]);

        // Create a new payment record for bounce charge (if any)
        if ($request->bounce_charge > 0) {
            $currentYear = AcademicYear::current();
            FeePayment::create([
                'student_id'       => $payment->student_id,
                'fee_head_id'      => $payment->fee_head_id,
                'academic_year_id' => $currentYear?->id,
                'receipt_number'   => $this->generateReceiptNumber(),
                'payment_date'     => today(),
                'amount'           => $request->bounce_charge,
                'late_fee'         => 0,
                'discount'         => 0,
                'total_paid'       => $request->bounce_charge,
                'payment_mode'     => 'cash',
                'remarks'          => 'Cheque bounce charge - original receipt: ' . $payment->receipt_number,
                'collected_by'     => auth()->id(),
            ]);
        }

        return back()->with('success', 'Cheque marked as bounced. Original payment reversed.');
    }

    // ── Bank Reconciliation Report ─────────────────────────

    public function bankReconciliation(Request $request)
    {
        $month  = $request->month ?? now()->format('Y-m');
        $status = $request->status; // pending / cleared / bounced / all

        $payments = FeePayment::with(['student', 'feeHead'])
            ->whereIn('payment_mode', ['cheque', 'dd', 'neft', 'rtgs', 'imps', 'bank_transfer'])
            ->where('is_cancelled', false)
            ->whereRaw("TO_CHAR(payment_date, 'YYYY-MM') = ?", [$month])
            ->when($status, fn($q, $v) => $q->where('cheque_status', $v))
            ->orderBy('cheque_date')
            ->get();

        $summary = [
            'total'         => $payments->count(),
            'total_amount'  => $payments->sum('total_paid'),
            'pending'       => $payments->where('cheque_status', 'pending')->count(),
            'pending_amt'   => $payments->where('cheque_status', 'pending')->sum('total_paid'),
            'cleared'       => $payments->where('cheque_status', 'cleared')->count(),
            'cleared_amt'   => $payments->where('cheque_status', 'cleared')->sum('total_paid'),
            'bounced'       => $payments->where('cheque_status', 'bounced')->count(),
            'bounced_amt'   => $payments->where('cheque_status', 'bounced')->sum('total_paid'),
        ];

        return view('fees.bank-reconciliation', compact('payments', 'summary', 'month', 'status'));
    }

    // ── Fine / Late Fee Collection Report ─────────────────

    public function fineReport(Request $request)
    {
        $currentYear = AcademicYear::current();
        $classes     = Classes::active()->get();

        $query = FeePayment::with(['student', 'feeHead', 'student.currentEnrollment.class'])
            ->where('is_cancelled', false)
            ->where('late_fee', '>', 0)
            ->when($request->from_date, fn($q) => $q->whereDate('payment_date', '>=', $request->from_date))
            ->when($request->to_date,   fn($q) => $q->whereDate('payment_date', '<=', $request->to_date))
            ->when($request->class_id,  fn($q) => $q->whereHas('student.currentEnrollment', fn($sq) => $sq->where('class_id', $request->class_id)));

        $payments = $query->orderBy('payment_date', 'desc')->get();
        $totalFine = $payments->sum('late_fee');

        return view('fees.fine-report', compact('payments', 'totalFine', 'classes'));
    }

    // ── Fee Balance Carry-Forward ──────────────────────────

    public function carryForwards(Request $request)
    {
        $years         = AcademicYear::orderByDesc('start_date')->get();
        $currentYear   = AcademicYear::current();
        $carryForwards = \App\Models\FeeCarryForward::with(['student', 'fromYear', 'toYear'])
            ->when($request->to_year_id, fn($q) => $q->where('to_academic_year_id', $request->to_year_id))
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();
        return view('fees.carry-forwards', compact('years', 'currentYear', 'carryForwards'));
    }

    public function storeCarryForward(Request $request)
    {
        $request->validate([
            'student_id'            => 'required|exists:students,id',
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id|different:from_academic_year_id',
            'outstanding_amount'    => 'required|numeric|min:0.01',
            'note'                  => 'nullable|string|max:500',
        ]);

        \App\Models\FeeCarryForward::updateOrCreate(
            [
                'student_id'            => $request->student_id,
                'from_academic_year_id' => $request->from_academic_year_id,
                'to_academic_year_id'   => $request->to_academic_year_id,
            ],
            [
                'outstanding_amount' => $request->outstanding_amount,
                'note'               => $request->note,
                'created_by'         => auth()->id(),
            ]
        );

        return back()->with('success', 'Fee carry-forward recorded.');
    }

    public function recordCarryForwardRecovery(Request $request, int $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $cf = \App\Models\FeeCarryForward::findOrFail($id);
        $cf->update(['recovered_amount' => $cf->recovered_amount + $request->amount]);
        return back()->with('success', '₹' . number_format($request->amount, 2) . ' carry-forward recovery recorded.');
    }

    // ── Advance Payment ───────────────────────────────────

    public function advancePayments(Request $request)
    {
        $currentYear = AcademicYear::current();
        $student     = null;
        $advances    = collect();
        $totalBalance = 0;

        if ($request->student_id) {
            $student = Student::with('currentEnrollment.class')->find($request->student_id);
            if ($student) {
                $advances = DB::table('fee_advance_payments')
                    ->where('student_id', $student->id)
                    ->orderByDesc('payment_date')
                    ->get();
                $totalBalance = $advances->sum('remaining_amount');
            }
        }

        return view('fees.advance-payments', compact('student', 'advances', 'totalBalance', 'currentYear'));
    }

    public function storeAdvancePayment(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,id',
            'amount'       => 'required|numeric|min:1',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_mode' => 'required|in:cash,cheque,dd,online,upi',
        ]);
        $currentYear = AcademicYear::current();
        $receiptNum  = 'ADV-' . now()->format('Y') . '-' . str_pad(
            DB::table('fee_advance_payments')->count() + 1, 4, '0', STR_PAD_LEFT
        );
        DB::table('fee_advance_payments')->insert([
            'student_id'       => $request->student_id,
            'academic_year_id' => $currentYear?->id,
            'amount'           => $request->amount,
            'applied_amount'   => 0,
            'remaining_amount' => $request->amount,
            'payment_date'     => $request->payment_date,
            'receipt_number'   => $receiptNum,
            'payment_mode'     => $request->payment_mode,
            'transaction_id'   => $request->transaction_id,
            'notes'            => $request->notes,
            'recorded_by'      => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        return back()->with('success', "Advance payment of ₹{$request->amount} recorded. Receipt: {$receiptNum}");
    }

    public function applyAdvance(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'fee_head_id' => 'required|exists:fee_heads,id',
            'amount'      => 'required|numeric|min:1',
        ]);

        $student     = Student::findOrFail($request->student_id);
        $currentYear = AcademicYear::current();

        // Get total available advance balance
        $availableBalance = DB::table('fee_advance_payments')
            ->where('student_id', $student->id)
            ->sum('remaining_amount');

        if ($request->amount > $availableBalance) {
            return back()->withErrors(['amount' => "Insufficient advance balance. Available: ₹" . number_format($availableBalance, 2)]);
        }

        // Deduct from oldest advances first (FIFO)
        $remaining = $request->amount;
        $advances  = DB::table('fee_advance_payments')
            ->where('student_id', $student->id)
            ->where('remaining_amount', '>', 0)
            ->orderBy('payment_date')
            ->get();

        foreach ($advances as $adv) {
            if ($remaining <= 0) break;
            $deduct = min($remaining, $adv->remaining_amount);
            DB::table('fee_advance_payments')->where('id', $adv->id)->update([
                'applied_amount'  => $adv->applied_amount + $deduct,
                'remaining_amount'=> $adv->remaining_amount - $deduct,
                'updated_at'      => now(),
            ]);
            $remaining -= $deduct;
        }

        // Record as a fee payment with advance applied
        $payment = FeePayment::create([
            'student_id'       => $student->id,
            'fee_head_id'      => $request->fee_head_id,
            'amount'           => $request->amount,
            'advance_applied'  => $request->amount,
            'late_fee'         => 0,
            'discount'         => 0,
            'total_paid'       => $request->amount,
            'payment_date'     => today()->toDateString(),
            'payment_mode'     => 'advance',
            'receipt_number'   => $this->generateReceiptNumber(),
            'academic_year_id' => $currentYear?->id,
            'collected_by'     => Auth::id(),
            'remarks'          => 'Applied from advance balance',
        ]);

        return redirect()->route('fees.advance-payments', ['student_id' => $student->id])
            ->with('success', "₹" . number_format($request->amount, 2) . " applied from advance balance. Receipt #{$payment->receipt_number}");
    }

    public function studentCustomFee(Request $request)
    {
        $currentYear = AcademicYear::current();
        $feeHeads    = FeeHead::where('is_active', true)->get();
        $student     = null;
        $customFees  = collect();

        if ($request->student_id) {
            $student    = \App\Models\Student::findOrFail($request->student_id);
            $customFees = \App\Models\StudentCustomFee::where('student_id', $student->id)
                ->where('academic_year_id', $currentYear?->id)
                ->with('feeHead')->get();
        }

        return view('fees.student-custom-fee', compact('feeHeads', 'student', 'customFees', 'currentYear'));
    }

    public function saveStudentCustomFee(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,id',
            'fee_head_id' => 'required|exists:fee_heads,id',
            'amount'      => 'required|numeric|min:0',
        ]);
        $year = AcademicYear::current();
        \App\Models\StudentCustomFee::updateOrCreate(
            ['student_id' => $request->student_id, 'fee_head_id' => $request->fee_head_id, 'academic_year_id' => $year?->id],
            ['custom_amount' => $request->amount, 'reason' => $request->reason, 'set_by' => Auth::id()]
        );
        return back()->with('success', 'Custom fee saved for student.');
    }

    public function categoryFeeVariations(Request $request)
    {
        $classes     = Classes::active()->get();
        $feeHeads    = FeeHead::where('is_active', true)->get();
        $structures  = FeeStructure::with(['class', 'feeHead'])->get();
        $variations  = \App\Models\CategoryFeeVariation::with(['feeStructure.class', 'feeHead'])->get()
            ->groupBy('fee_structure_id');

        return view('fees.category-fee-variations', compact('classes', 'feeHeads', 'structures', 'variations'));
    }

    public function saveCategoryFeeVariation(Request $request)
    {
        $request->validate([
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'fee_head_id'      => 'required|exists:fee_heads,id',
            'variations'       => 'required|array',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->variations as $category => $amount) {
                if ($amount !== null && $amount !== '') {
                    \App\Models\CategoryFeeVariation::updateOrCreate(
                        [
                            'fee_structure_id' => $request->fee_structure_id,
                            'fee_head_id'      => $request->fee_head_id,
                            'student_category' => $category,
                        ],
                        ['amount' => (float)$amount, 'remarks' => $request->remarks]
                    );
                }
            }
        });
        return back()->with('success', 'Category fee variations saved.');
    }

    public function cashierReport(Request $request)
    {
        $from = $request->from_date ?? today()->toDateString();
        $to   = $request->to_date   ?? today()->toDateString();

        $rows = \App\Models\FeePayment::with('collectedBy')
            ->whereBetween('payment_date', [$from, $to])
            ->where('is_cancelled', false)
            ->selectRaw('collected_by, payment_mode, COUNT(*) as txn_count, SUM(total_paid) as total')
            ->groupBy('collected_by', 'payment_mode')
            ->get()
            ->groupBy('collected_by');

        $users = \App\Models\User::whereIn('id', $rows->keys())->pluck('name', 'id');

        return view('fees.cashier-report', compact('rows', 'users', 'from', 'to'));
    }

    public function concessionReport(Request $request)
    {
        $year    = AcademicYear::current();
        $classes = Classes::active()->get();

        $query = \App\Models\StudentConcession::with(['student.currentEnrollment.class', 'scheme'])
            ->where('status', 'active');

        if ($request->class_id) {
            $query->whereHas('student.currentEnrollment', fn($q) => $q->where('class_id', $request->class_id));
        }
        if ($request->concession_type) {
            $query->where('concession_type', $request->concession_type);
        }

        $concessions = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        $summary = \App\Models\StudentConcession::where('status', 'active')
            ->selectRaw('concession_type, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('concession_type')
            ->get();

        return view('fees.concession-report', compact('concessions', 'summary', 'classes'));
    }

    // ── Fee Reminder Config ───────────────────────────────

    public function reminderConfig()
    {
        $config = FeeReminderConfig::first() ?? new FeeReminderConfig([
            'name'            => 'Default Schedule',
            'before_due_days' => [3, 1],
            'on_due_date'     => true,
            'after_due_days'  => [7, 15, 30],
            'channel'         => 'email',
        ]);
        return view('fees.reminder-config', compact('config'));
    }

    public function saveReminderConfig(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'before_due_days' => 'nullable|string',
            'on_due_date'     => 'nullable',
            'after_due_days'  => 'nullable|string',
            'channel'         => 'required|in:email,sms,whatsapp,email_sms',
            'email_subject'   => 'nullable|string|max:255',
            'email_body'      => 'nullable|string',
        ]);

        $data['before_due_days'] = array_filter(array_map('intval', explode(',', $data['before_due_days'] ?? '')));
        $data['after_due_days']  = array_filter(array_map('intval', explode(',', $data['after_due_days'] ?? '')));
        $data['on_due_date']     = $request->boolean('on_due_date');
        $data['is_active']       = true;
        $data['updated_by']      = Auth::id();

        $config = FeeReminderConfig::first();
        if ($config) {
            $config->update($data);
        } else {
            FeeReminderConfig::create($data);
        }

        return back()->with('success', 'Fee reminder schedule saved.');
    }

    public function sendReminders(Request $request)
    {
        $request->validate(['class_id' => 'nullable|exists:classes,id']);

        $currentYear = AcademicYear::current();
        if (!$currentYear) {
            return back()->with('error', 'No active academic year.');
        }

        $config = FeeReminderConfig::first();
        if (!$config || !in_array('email', explode('_', $config->channel))) {
            return back()->with('error', 'Email channel not configured in reminder settings.');
        }

        $query = Student::with(['currentEnrollment.class', 'user', 'parent'])
            ->where('status', 'active');

        if ($request->class_id) {
            $query->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $request->class_id));
        }

        $sent = 0;
        $skipped = 0;

        foreach ($query->cursor() as $student) {
            $email = $student->user?->email ?? $student->parent?->email ?? null;
            if (!$email) { $skipped++; continue; }

            // Compute outstanding balance
            $paid = FeePayment::where('student_id', $student->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('is_cancelled', false)
                ->selectRaw('fee_head_id, SUM(total_paid) as paid')
                ->groupBy('fee_head_id')
                ->pluck('paid', 'fee_head_id');

            $enrollment = $student->currentEnrollment;
            $classId = $enrollment?->class_id;
            $dues = FeeStructure::where('class_id', $classId)
                ->where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->with('feeHead')
                ->get();

            $balance = $dues->sum(fn($d) => max(0, $d->amount - ($paid[$d->fee_head_id] ?? 0)));
            if ($balance <= 0) { $skipped++; continue; }

            $subject = strtr($config->email_subject ?: 'Fee Payment Reminder', [
                '{{school_name}}' => config('app.name', 'School'),
                '{{student_name}}' => $student->full_name,
            ]);
            $body = strtr($config->email_body ?: "Dear Parent,\n\nThis is a reminder that ₹{{balance}} is due.\n\nRegards,\n{{school_name}}", [
                '{{parent_name}}'  => $student->parent?->name ?? 'Parent/Guardian',
                '{{student_name}}' => $student->full_name,
                '{{class}}'        => $enrollment?->class?->name ?? '',
                '{{balance}}'      => number_format($balance, 2),
                '{{due_date}}'     => now()->format('d M Y'),
                '{{school_name}}' => config('app.name', 'School'),
            ]);

            try {
                Mail::to($email)->send(new FeeReminderMail($subject, $body));
                $sent++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return back()->with('success', "Reminders sent: {$sent}. Skipped (no email / no dues): {$skipped}.");
    }

    // ── Fee Change History Log ────────────────────────────

    public function feeChangeHistory(Request $request)
    {
        $classes     = Classes::active()->get();
        $currentYear = AcademicYear::current();

        $logs = \App\Models\FeeChangeLog::with(['class', 'feeHead', 'changedBy'])
            ->when($request->class_id, fn($q, $v) => $q->where('class_id', $v))
            ->when($request->change_type, fn($q, $v) => $q->where('change_type', $v))
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('fees.change-history', compact('logs', 'classes', 'currentYear'));
    }
}
