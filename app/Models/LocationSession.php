<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationSession extends Model
{
    protected $fillable = [
        'sales_rep_id',
        'date',
        'start_time',
        'end_time',
        'total_active_minutes',
        'total_inactive_minutes',
        'last_seen',
        'is_online',
        'battery_level',
        'battery_charging',
        'activities',       // JSON array of area activities
    ];

    protected $casts = [
        'date'                   => 'date',
        'start_time'             => 'datetime',
        'end_time'               => 'datetime',
        'last_seen'              => 'datetime',
        'is_online'              => 'boolean',
        'battery_charging'       => 'boolean',
        'activities'             => 'array',
    ];

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }
}
