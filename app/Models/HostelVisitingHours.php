<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelVisitingHours extends Model
{
    protected $fillable = ['hostel_id', 'day_type', 'from_time', 'to_time', 'is_active', 'note'];
    protected $casts = ['is_active' => 'boolean'];

    public function hostel() { return $this->belongsTo(Hostel::class); }

    public static function isCurrentlyAllowed(?int $hostelId = null): bool
    {
        $now = now();
        $dayType = $now->isWeekend() ? 'weekend' : 'weekday';
        $timeNow = $now->format('H:i:s');

        $rule = self::where('is_active', true)
            ->where(fn($q) => $q->whereIn('day_type', ['all', $dayType]))
            ->when($hostelId, fn($q) => $q->where(fn($q2) => $q2->where('hostel_id', $hostelId)->orWhereNull('hostel_id')))
            ->orderByRaw("CASE WHEN day_type = ? THEN 1 WHEN day_type = 'all' THEN 2 ELSE 3 END", [$dayType])
            ->first();

        if (!$rule) return true; // no restriction configured = always allowed

        return $timeNow >= $rule->from_time && $timeNow <= $rule->to_time;
    }
}
