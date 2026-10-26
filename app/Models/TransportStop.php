<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportStop extends Model
{
    protected $fillable = [
        'route_id',
        'vehicle_id',
        'van_number',
        'name',
        'stop_order',
        'distance_km',
        'arrival_time',
        'pickup_time',
        'drop_time',
        'pickup_instructions',
        'drop_instructions',
        'remarks',
        'fare',
        'landmark',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'fare'         => 'decimal:2',
        'distance_km'  => 'decimal:2',
        'stop_order'   => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function allotments(): HasMany
    {
        return $this->hasMany(TransportAllotment::class, 'stop_id');
    }

    public function activeAllotments(): HasMany
    {
        return $this->hasMany(TransportAllotment::class, 'stop_id')->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getEffectiveVanNumberAttribute(): string
    {
        return $this->van_number 
            ?: ($this->vehicle?->vehicle_number 
            ?: ($this->route?->vehicle?->vehicle_number ?: ('Van #' . ($this->route_id ?: '1'))));
    }

    public function getFormattedPickupTimeAttribute(): string
    {
        if (empty($this->pickup_time)) {
            return 'Not Set';
        }
        return date('h:i A', strtotime($this->pickup_time));
    }

    public function getFormattedDropTimeAttribute(): string
    {
        if (empty($this->drop_time)) {
            return 'Not Set';
        }
        return date('h:i A', strtotime($this->drop_time));
    }

    public function getFormattedFareAttribute(): string
    {
        return '₹' . number_format($this->fare ?: 0, 0);
    }
}
