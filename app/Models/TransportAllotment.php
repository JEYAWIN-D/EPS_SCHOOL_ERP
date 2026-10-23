<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAllotment extends Model
{
    protected $fillable = [
        'student_id', 'enrollment_id', 'route_id', 'stop_id', 'vehicle_id',
        'fee', 'pickup_time', 'drop_time', 'boarding_stop',
        'pickup_stop', 'is_active', 'academic_year_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee'       => 'decimal:2',
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
}
