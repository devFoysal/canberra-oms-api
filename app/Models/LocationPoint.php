<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPoint extends Model
{
    protected $fillable = [
        'sales_rep_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'battery_level',
        'battery_charging',
        'area',              // reverse geocoded (Google Maps API দিয়ে backend এ করা হয়)
        'recorded_at',       // client এ যখন captured হয়েছে
    ];

    protected $casts = [
        'latitude'         => 'float',
        'longitude'        => 'float',
        'accuracy'         => 'float',
        'speed'            => 'float',
        'heading'          => 'float',
        'battery_charging' => 'boolean',
        'recorded_at'      => 'datetime',
    ];

    // created_at = server এ যখন পৌঁছেছে, recorded_at = device এ যখন নেওয়া হয়েছে

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }
}
