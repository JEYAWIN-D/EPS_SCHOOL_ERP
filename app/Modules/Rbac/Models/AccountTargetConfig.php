<?php

namespace App\Modules\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTargetConfig extends Model
{
    protected $table = 'account_target_configs';

    protected $fillable = [
        'role_name',
        'target_count',
        'warning_threshold',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_count' => 'integer',
        'warning_threshold' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
