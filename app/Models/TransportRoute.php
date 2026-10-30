<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = [
        'route_name', 'route_number', 'route_code', 'from_location', 'to_location', 'distance_km',
        'vehicle_id', 'driver_name', 'driver_contact',
        'departure_time', 'arrival_time', 'start_time', 'end_time', 'fee', 'monthly_fee',
        'start_point', 'end_point', 'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'monthly_fee' => 'decimal:2',
    ];

    public function getRouteCodeAttribute(): ?string
    {
        return $this->attributes['route_code'] ?? $this->attributes['route_number'] ?? null;
    }

    public function setRouteCodeAttribute($value): void
    {
        $this->attributes['route_code'] = $value;
        if (empty($this->attributes['route_number'])) {
            $this->attributes['route_number'] = $value;
        }
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function stops(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportStop::class, 'route_id')->orderBy('stop_order');
    }

    public function allotments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransportAllotment::class, 'route_id')->where('is_active', true);
    }

    public function getOccupiedSeatsAttribute(): int
    {
        return $this->allotments()->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        $cap = $this->vehicle ? $this->vehicle->effective_capacity : 0;
        return max(0, $cap - $this->occupied_seats);
    }
}
