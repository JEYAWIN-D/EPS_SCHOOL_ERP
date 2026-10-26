<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportImportLog extends Model
{
    protected $table = 'transport_import_logs';

    protected $fillable = [
        'file_name',
        'import_type',
        'total_rows',
        'success_count',
        'duplicate_count',
        'invalid_count',
        'failed_count',
        'summary_json',
        'imported_by',
    ];

    protected $casts = [
        'summary_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
