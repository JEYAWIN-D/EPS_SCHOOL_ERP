<?php

namespace App\Modules\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class FieldPermission extends Model
{
    protected $table = 'field_permissions';

    protected $fillable = [
        'role_id',
        'user_id',
        'entity', // students, employees, fees, admissions
        'field_name', // salary, father_mobile, bank_account_no, etc.
        'permission_type', // hidden, masked, readonly, editable
        'created_by',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
