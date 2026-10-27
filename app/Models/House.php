<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class House extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'logo',
        'captain_student_id',
        'vice_captain_student_id',
        'teacher_id',
        'capacity',
        'academic_year_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'house_id');
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'captain_student_id');
    }

    public function viceCaptain(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'vice_captain_student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('name');
    }

    public static function allCached()
    {
        return Cache::remember('all_houses_list', 3600, function () {
            return static::where('is_active', true)->orderBy('name')->get();
        });
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('all_houses_list');
        });

        static::deleted(function () {
            Cache::forget('all_houses_list');
        });
    }
}

