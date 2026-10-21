<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'student_id', 'enrollment_id', 'fee_head_id', 'term_number', 'term_name', 'academic_year_id',
        'receipt_number', 'payment_date', 'amount', 'late_fee', 'discount',
        'amount_paid', 'amount_adjusted', 'excess_amount', 'total_paid', 'payment_mode', 'payment_account', 'transaction_id',
        'cheque_number', 'cheque_bank', 'cheque_branch', 'cheque_date',
        'cheque_status', 'bounce_charge', 'bounce_reason',
        'remarks', 'collected_by', 'is_cancelled', 'cancel_reason',
        'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'payment_date'    => 'date',
        'cheque_date'     => 'date',
        'bounce_charge'   => 'decimal:2',
        'is_cancelled'    => 'boolean',
        'cancelled_at'    => 'datetime',
        'late_fee'        => 'decimal:2',
        'discount'        => 'decimal:2',
        'amount'          => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'amount_adjusted' => 'decimal:2',
        'excess_amount'   => 'decimal:2',
        'total_paid'      => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function splits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeePaymentSplit::class, 'fee_payment_id');
    }
}
