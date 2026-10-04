<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Visitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pass_number',
        'pass_token',
        'visitor_name',
        'category',
        'visitor_phone',
        'visitor_email',
        'visitor_id_type',
        'visitor_id_number',
        'purpose',
        'department',
        'whom_to_meet',
        'host_employee_id',
        'visit_date',
        'in_time',
        'expected_exit_time',
        'out_time',
        'visitor_count',
        'vehicle_type',
        'vehicle_number',
        'visitor_photo',
        'id_proof_document',
        'items_carried',
        'status',
        'badge_color',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'student_id',
        'relationship_to_student',
        'child_name',
        'grade_applying_for',
        'enquiry_source',
        'enquiry_id',
        'company_name',
        'work_order_number',
        'vendor_id',
        'candidate_ref_number',
        'job_role_applied',
        'remarks',
        'metadata',
        'logged_by',
        'is_blacklisted',
    ];

    protected $casts = [
        'visit_date'         => 'date',
        'in_time'            => 'datetime',
        'expected_exit_time' => 'datetime',
        'out_time'           => 'datetime',
        'approved_at'        => 'datetime',
        'rejected_at'        => 'datetime',
        'visitor_count'      => 'integer',
        'is_blacklisted'     => 'boolean',
        'metadata'           => 'array',
    ];

    public const CATEGORY_PARENT    = 'parent';
    public const CATEGORY_ADMISSION = 'admission_enquiry';
    public const CATEGORY_VENDOR    = 'vendor';
    public const CATEGORY_INTERVIEW = 'interview';
    public const CATEGORY_GUEST     = 'guest';
    public const CATEGORY_OTHER     = 'other';

    public const STATUS_PENDING     = 'pending_approval';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_CHECKED_IN  = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_REJECTED    = 'rejected';
    public const STATUS_CANCELLED   = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (Visitor $visitor) {
            if (empty($visitor->pass_token)) {
                $visitor->pass_token = Str::random(32);
            }
            if (empty($visitor->pass_number)) {
                $visitor->pass_number = self::generatePassNumber();
            }
            if (empty($visitor->visit_date)) {
                $visitor->visit_date = today();
            }
            if ($visitor->status === self::STATUS_CHECKED_IN && empty($visitor->in_time)) {
                $visitor->in_time = now();
            }
            if (empty($visitor->badge_color)) {
                $visitor->badge_color = self::getDefaultBadgeColor($visitor->category);
            }
        });
    }

    public static function generatePassNumber(): string
    {
        $prefix = 'VP-' . now()->format('Ymd') . '-';
        $todayCount = self::whereDate('created_at', today())->count() + 1;
        return $prefix . str_pad((string) $todayCount, 4, '0', STR_PAD_LEFT);
    }

    public static function getDefaultBadgeColor(?string $category): string
    {
        return match ($category) {
            self::CATEGORY_PARENT    => 'emerald',
            self::CATEGORY_ADMISSION => 'blue',
            self::CATEGORY_VENDOR    => 'amber',
            self::CATEGORY_INTERVIEW => 'purple',
            self::CATEGORY_GUEST     => 'indigo',
            default                  => 'slate',
        };
    }

    // Relationships
    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function hostEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'host_employee_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class, 'enquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // State Checks
    public function isInside(): bool
    {
        return is_null($this->out_time) && in_array($this->status, [self::STATUS_CHECKED_IN, self::STATUS_APPROVED]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_CHECKED_IN]);
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isOverstayed(): bool
    {
        return $this->isInside() && $this->expected_exit_time && now()->greaterThan($this->expected_exit_time);
    }

    // Scopes
    public function scopeActiveInside($query)
    {
        return $query->whereNull('out_time')->whereIn('status', [self::STATUS_CHECKED_IN, self::STATUS_APPROVED]);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopeForHost($query, $hostEmployeeId)
    {
        return $query->where('host_employee_id', $hostEmployeeId);
    }

    // Actions
    public function approve(User $user, ?string $notes = null): bool
    {
        $this->update([
            'status'      => self::STATUS_CHECKED_IN,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'in_time'     => $this->in_time ?? now(),
            'remarks'     => $notes ? ($this->remarks . "\nApproval note: " . $notes) : $this->remarks,
        ]);
        return true;
    }

    public function reject(User $user, string $reason): bool
    {
        $this->update([
            'status'           => self::STATUS_REJECTED,
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);
        return true;
    }

    public function checkIn(): bool
    {
        return $this->update([
            'status'  => self::STATUS_CHECKED_IN,
            'in_time' => now(),
        ]);
    }

    public function checkOut(): bool
    {
        return $this->update([
            'status'   => self::STATUS_CHECKED_OUT,
            'out_time' => now(),
        ]);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_PARENT    => 'Parent / Guardian',
            self::CATEGORY_ADMISSION => 'Admission Enquiry',
            self::CATEGORY_VENDOR    => 'Vendor / Maintenance',
            self::CATEGORY_INTERVIEW => 'Staff Interview',
            self::CATEGORY_GUEST     => 'Official Guest / VIP',
            default                  => 'General Visitor / Other',
        };
    }

    public function getCategoryBadgeClassesAttribute(): array
    {
        return match ($this->category) {
            self::CATEGORY_PARENT    => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'badge' => 'bg-emerald-500 text-white', 'border' => 'border-emerald-500'],
            self::CATEGORY_ADMISSION => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'badge' => 'bg-blue-600 text-white', 'border' => 'border-blue-500'],
            self::CATEGORY_VENDOR    => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'badge' => 'bg-amber-600 text-white', 'border' => 'border-amber-500'],
            self::CATEGORY_INTERVIEW => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200', 'badge' => 'bg-purple-600 text-white', 'border' => 'border-purple-500'],
            self::CATEGORY_GUEST     => ['bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'badge' => 'bg-indigo-600 text-white', 'border' => 'border-indigo-500'],
            default                  => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'badge' => 'bg-slate-600 text-white', 'border' => 'border-slate-500'],
        };
    }

    public function getStatusBadgeClassesAttribute(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING     => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'text' => 'Pending Approval', 'dot' => 'bg-amber-500 animate-pulse'],
            self::STATUS_APPROVED    => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'text' => 'Approved', 'dot' => 'bg-blue-500'],
            self::STATUS_CHECKED_IN  => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'text' => 'Inside Premises', 'dot' => 'bg-emerald-500'],
            self::STATUS_CHECKED_OUT => ['bg' => 'bg-slate-100 text-slate-600 border-slate-200', 'text' => 'Checked Out', 'dot' => 'bg-slate-400'],
            self::STATUS_REJECTED    => ['bg' => 'bg-red-50 text-red-700 border-red-200', 'text' => 'Rejected', 'dot' => 'bg-red-500'],
            self::STATUS_CANCELLED   => ['bg' => 'bg-gray-100 text-gray-500 border-gray-200', 'text' => 'Cancelled', 'dot' => 'bg-gray-400'],
            default                  => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'text' => ucfirst($this->status), 'dot' => 'bg-slate-400'],
        };
    }
}
