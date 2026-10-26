<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdCardGenerationJob extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'job_type',
        'requested_by',
        'filters_json',
        'total_records',
        'processed_records',
        'failed_records',
        'job_status',
        'output_file_reference',
        'failed_details_json',
        'created_at',
        'completed_at',
    ];

    protected $casts = [
        'filters_json'        => 'array',
        'failed_details_json' => 'array',
        'total_records'       => 'integer',
        'processed_records'   => 'integer',
        'failed_records'      => 'integer',
        'created_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
