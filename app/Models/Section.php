<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Section extends Model
{
    protected $fillable = ['class_id', 'academic_year_id', 'name', 'capacity', 'is_active', 'class_teacher_id', 'co_class_teacher_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function class(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function classTeacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function coClassTeacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'co_class_teacher_id');
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function timetables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Timetable::class, 'section_id');
    }

    public function activeStudentsCount(): int
    {
        return $this->enrollments()->where('status', 'active')->count();
    }

    public static function allCached()
    {
        return Cache::remember('all_sections_list', 3600, function () {
            return static::all();
        });
    }

    protected static function booted(): void
    {
        static::saved(function() {
            Cache::forget('all_sections_list');
            Cache::forget('active_classes_with_sections_list');
        });
        static::deleted(function() {
            Cache::forget('all_sections_list');
            Cache::forget('active_classes_with_sections_list');
        });
    }
}

