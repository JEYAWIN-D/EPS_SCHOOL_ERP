<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'admin_pin',
        'mobile',
        'employee_id',
        'student_id',
        'is_active',
        'must_change_password',
        'last_login_at',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'admin_pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'last_login_at'       => 'datetime',
            'password'            => 'hashed',
            'admin_pin'           => 'hashed',
            'is_active'           => 'boolean',
            'must_change_password'=> 'boolean',
            'deleted_at'          => 'datetime',
        ];
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function dataScopes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Rbac\Models\UserDataScope::class);
    }

    public function fieldPermissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Rbac\Models\FieldPermission::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get the effective field permission for an entity/field.
     * Returns: 'editable' | 'readonly' | 'masked' | 'hidden'
     */
    public function getEffectiveFieldPermission(string $entity, string $field): string
    {
        if ($this->isSuperAdmin()) {
            return 'editable';
        }

        // Direct user override takes precedence
        $userFp = $this->fieldPermissions()
            ->where('entity', $entity)
            ->where('field_name', $field)
            ->first();

        if ($userFp) {
            return $userFp->permission_type;
        }

        // Otherwise check role-level field permissions
        $roleIds = $this->roles->pluck('id');
        $roleFp = \App\Modules\Rbac\Models\FieldPermission::whereIn('role_id', $roleIds)
            ->where('entity', $entity)
            ->where('field_name', $field)
            ->orderByRaw("CASE 
                WHEN permission_type = 'hidden' THEN 1 
                WHEN permission_type = 'masked' THEN 2 
                WHEN permission_type = 'readonly' THEN 3 
                ELSE 4 END ASC")
            ->first();

        return $roleFp ? $roleFp->permission_type : 'editable';
    }

    /**
     * Get array of scope values for a given scope type, or null if unrestricted.
     */
    public function getScopeValues(string $scopeType): ?array
    {
        if ($this->isSuperAdmin()) {
            return null; // Null indicates unrestricted access
        }

        $scopes = $this->dataScopes()
            ->where('scope_type', $scopeType)
            ->where('is_active', true)
            ->get();

        if ($scopes->isEmpty()) {
            // Check if user has an 'all' scope explicitly defined
            $allScope = $this->dataScopes()->where('scope_type', 'all')->where('is_active', true)->exists();
            if ($allScope) {
                return null;
            }
            return []; // Restricted / empty
        }

        $values = [];
        foreach ($scopes as $scope) {
            $values = array_merge($values, $scope->parsed_values);
        }

        return array_values(array_unique($values));
    }
}
