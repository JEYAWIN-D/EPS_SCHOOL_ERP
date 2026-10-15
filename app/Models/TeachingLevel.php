<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'min_class_numeric',
        'max_class_numeric',
        'schedule_type',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'min_class_numeric' => 'integer',
        'max_class_numeric' => 'integer',
        'display_order'     => 'integer',
        'is_active'         => 'boolean',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class, 'teaching_level_id')->orderBy('sort_order');
    }

    public function eligibilities(): HasMany
    {
        return $this->hasMany(TeacherLevelEligibility::class, 'teaching_level_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TeachingLevelActivity::class, 'class_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    public function getBadgeClassAttribute(): string
    {
        return match($this->code) {
            'kg'               => 'badge-rose',
            'primary'          => 'badge-indigo',
            'secondary'        => 'badge-blue',
            'higher_secondary' => 'badge-purple',
            default            => 'badge-slate',
        };
    }
}
