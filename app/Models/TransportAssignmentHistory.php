<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAssignmentHistory extends Model
{
    protected $table = 'transport_assignment_histories';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'old_vehicle_id',
        'new_vehicle_id',
        'old_route_id',
        'new_route_id',
        'old_stop_id',
        'new_stop_id',
        'old_pickup_time',
        'new_pickup_time',
        'old_drop_time',
        'new_drop_time',
        'old_fee',
        'new_fee',
        'change_type',
        'effective_date',
        'remarks',
        'changed_by',
    ];

    protected $casts = [
        'old_fee'        => 'decimal:2',
        'new_fee'        => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function oldVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'old_vehicle_id');
    }

    public function newVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'new_vehicle_id');
    }

    public function oldRoute(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'old_route_id');
    }

    public function newRoute(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'new_route_id');
    }

    public function oldStop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'old_stop_id');
    }

    public function newStop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'new_stop_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
