<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class IdCard extends Model
{
    use HasFactory;

    const STATUS_DRAFT               = 'draft';
    const STATUS_PENDING_VERIFY      = 'pending_verification';
    const STATUS_GENERATED           = 'generated';
    const STATUS_ISSUED              = 'issued';
    const STATUS_ACTIVE              = 'active';
    const STATUS_EXPIRED             = 'expired';
    const STATUS_LOST                = 'lost';
    const STATUS_DAMAGED             = 'damaged';
    const STATUS_REPLACED            = 'replaced';
    const STATUS_REVOKED             = 'revoked';

    const TYPE_STUDENT               = 'student';
    const TYPE_STAFF                 = 'staff';
    const TYPE_VISITOR               = 'visitor';

    protected $fillable = [
        'card_number',
        'card_type',
        'linked_entity_type',
        'linked_entity_id',
        'academic_year_id',
        'template_id',
        'template_version',
        'orientation',
        'side',
        'issue_date',
        'expiry_date',
        'status',
        'reprint_count',
        'reprint_reason',
        'qr_verification_token',
        'barcode_value',
        'field_snapshot_json',
        'generated_at',
        'issued_by',
    ];

    protected $casts = [
        'field_snapshot_json' => 'array',
        'issue_date'          => 'date',
        'expiry_date'         => 'date',
        'generated_at'        => 'datetime',
        'reprint_count'       => 'integer',
        'template_version'    => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->qr_verification_token)) {
                $model->qr_verification_token = (string) Str::uuid();
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(IdCardTemplate::class, 'template_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(IdCardAuditLog::class, 'card_id')->orderBy('timestamp', 'desc');
    }

    public function linkedEntity(): MorphTo
    {
        return $this->morphTo('linkedEntity', 'linked_entity_type', 'linked_entity_id');
    }

    public function getStudentAttribute(): ?Student
    {
        if ($this->card_type === self::TYPE_STUDENT && $this->linked_entity_type === Student::class) {
            return $this->linkedEntity;
        }
        return null;
    }

    public function getEmployeeAttribute(): ?Employee
    {
        if ($this->card_type === self::TYPE_STAFF && $this->linked_entity_type === Employee::class) {
            return $this->linkedEntity;
        }
        return null;
    }

    public function getVisitorAttribute(): ?Visitor
    {
        if ($this->card_type === self::TYPE_VISITOR && $this->linked_entity_type === Visitor::class) {
            return $this->linkedEntity;
        }
        return null;
    }

    public function isCardValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE && $this->status !== self::STATUS_ISSUED) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    public function getQrVerificationUrl(): string
    {
        return route('id-cards.verify', ['token' => $this->qr_verification_token]);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE, self::STATUS_ISSUED => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::STATUS_GENERATED => 'bg-blue-100 text-blue-800 border-blue-300',
            self::STATUS_DRAFT, self::STATUS_PENDING_VERIFY => 'bg-amber-100 text-amber-800 border-amber-300',
            self::STATUS_EXPIRED => 'bg-slate-100 text-slate-800 border-slate-300',
            self::STATUS_LOST, self::STATUS_DAMAGED => 'bg-rose-100 text-rose-800 border-rose-300',
            self::STATUS_REPLACED => 'bg-purple-100 text-purple-800 border-purple-300',
            self::STATUS_REVOKED => 'bg-red-100 text-red-800 border-red-300',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
