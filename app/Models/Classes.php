<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Classes extends Model
{
    protected $table = 'classes';

    protected $fillable = ['name', 'numeric_value', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getNameAttribute($value)
    {
        if ($value !== null && is_numeric($value)) {
            return static::toRoman((int)$value);
        }
        return $value;
    }

    public function subjects(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Subject::class, Timetable::class, 'class_id', 'id', 'id', 'subject_id')->distinct();
    }

    public function timetables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Timetable::class, 'class_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->numeric_value >= 1 && $this->numeric_value <= 12) {
            $ordinals = [
                1 => '1st Standard', 2 => '2nd Standard', 3 => '3rd Standard',
                4 => '4th Standard', 5 => '5th Standard', 6 => '6th Standard',
                7 => '7th Standard', 8 => '8th Standard', 9 => '9th Standard',
                10 => '10th Standard', 11 => '11th Standard', 12 => '12th Standard'
            ];
            return $ordinals[$this->numeric_value] ?? "Class {$this->name}";
        }
        return $this->name;
    }

    public function getCategoryAttribute(): string
    {
        $nv = (int) $this->numeric_value;
        if ($nv === 0) return 'pre-primary';
        if ($nv >= 1 && $nv <= 5) return 'primary';
        if ($nv >= 6 && $nv <= 8) return 'middle';
        if ($nv >= 9 && $nv <= 10) return 'secondary';
        if ($nv >= 11 && $nv <= 12) return 'higher-secondary';
        return 'general';
    }

    public static function toRoman($num): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            11 => 'XI', 12 => 'XII'
        ];
        return $map[(int)$num] ?? (string)$num;
    }

    public static function activeCached()
    {
        return Cache::remember('active_classes_list', 3600, function () {
            return static::active()->get();
        });
    }

    public static function activeWithSectionsCached()
    {
        return Cache::remember('active_classes_with_sections_list', 3600, function () {
            return static::with(['sections' => fn($q) => $q->where('is_active', true)->orderBy('name', 'asc')])
                ->active()
                ->get();
        });
    }

    public static function clearClassCache(): void
    {
        Cache::forget('active_classes_list');
        Cache::forget('active_classes_with_sections_list');
    }

    protected static function booted(): void
    {
        static::saved(fn() => static::clearClassCache());
        static::deleted(fn() => static::clearClassCache());
    }
}

