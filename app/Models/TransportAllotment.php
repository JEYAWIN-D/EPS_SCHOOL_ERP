<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportAllotment extends Model
{
    protected $fillable = [
        'student_id',
        'enrollment_id',
        'route_id',
        'stop_id',
        'drop_stop_id',
        'vehicle_id',
        'fee',
        'pickup_time',
        'drop_time',
        'pickup_time_override',
        'drop_time_override',
        'boarding_stop',
        'pickup_stop',
        'drop_stop',
        'is_active',
        'status',
        'start_date',
        'end_date',
        'remarks',
        'discontinued_reason',
        'discontinued_at',
        'discontinued_by',
        'academic_year_id',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'pickup_time_override' => 'boolean',
        'drop_time_override'   => 'boolean',
        'fee'                  => 'decimal:2',
        'start_date'           => 'date',
        'end_date'             => 'date',
        'discontinued_at'      => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'stop_id');
    }

    public function dropStop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'drop_stop_id');
    }

    public function discontinuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discontinued_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransportAssignmentHistory::class, 'student_id', 'student_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'active');
        });
    }

    public function getEffectivePickupTimeAttribute(): ?string
    {
        if (!empty($this->pickup_time)) {
            return $this->pickup_time;
        }
        return $this->stop?->pickup_time;
    }

    public function getEffectiveDropTimeAttribute(): ?string
    {
        if (!empty($this->drop_time)) {
            return $this->drop_time;
        }
        return $this->dropStop?->drop_time ?? $this->stop?->drop_time;
    }

    public function getEffectivePickupStopNameAttribute(): string
    {
        return $this->stop?->name ?? $this->pickup_stop ?? $this->boarding_stop ?? '—';
    }

    public function getEffectiveDropStopNameAttribute(): string
    {
        return $this->dropStop?->name ?? $this->drop_stop ?? $this->stop?->name ?? $this->pickup_stop ?? '—';
    }
}
