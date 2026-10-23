<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportRosterPassenger extends Model
{
    protected $table = 'transport_roster_passengers';

    protected $fillable = [
        'van_number',
        'van_label',
        'route_id',
        'vehicle_id',
        'student_id',
        'serial_no',
        'student_name',
        'class_name',
        'area',
        'amount',
        'morning_time',
        'evening_time',
        'is_active',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'is_active'  => 'boolean',
        'serial_no'  => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function getIsDropOnlyAttribute(): bool
    {
        $m = strtolower(trim((string)$this->morning_time));
        return in_array($m, ['eve', 'evening', '-', '']);
    }

    public function getFormattedMorningTimeAttribute(): string
    {
        if ($this->is_drop_only) {
            return 'Evening Drop Only';
        }
        return $this->morning_time ?: '—';
    }

    public function getFormattedEveningTimeAttribute(): string
    {
        return $this->evening_time ?: '—';
    }
}
