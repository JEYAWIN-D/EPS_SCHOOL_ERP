<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeFamilyMember extends Model
{
    protected $fillable = [
        'employee_id',
        'member_name',
        'dob',
        'relationship',
        'contact_number',
        'is_verified',
        'permanent_address',
        'aadhaar_address',
    ];

    protected $casts = [
        'dob'         => 'date',
        'is_verified' => 'boolean',
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
