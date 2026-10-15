<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCareerHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'change_type',
        'previous_value',
        'new_value',
        'effective_date',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recorder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
