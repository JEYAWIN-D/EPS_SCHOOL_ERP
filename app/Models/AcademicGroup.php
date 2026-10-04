<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicGroup extends Model
{
    protected $table = 'academic_groups';

    protected $fillable = [
        'name',
        'code',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function checklists(): HasMany
    {
        return $this->hasMany(BookNoteChecklist::class, 'group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name');
    }
}
