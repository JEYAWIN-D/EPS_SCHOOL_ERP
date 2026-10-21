<?php

namespace App\Modules\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDataScope extends Model
{
    protected $table = 'user_data_scopes';

    protected $fillable = [
        'user_id',
        'scope_type', // all, academic_year, class_id, section_id, department, route_id, warehouse_id, assigned_students_only, own_records_only
        'scope_value', // JSON array or scalar string
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get scope values as an array.
     */
    public function getParsedValuesAttribute(): array
    {
        if (empty($this->scope_value)) {
            return [];
        }

        $decoded = json_decode($this->scope_value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return array_map('trim', explode(',', (string) $this->scope_value));
    }
}
