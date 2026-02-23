<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'device_id',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'start_time',
        'end_time',
        'total_distance',
        'max_speed',
        'avg_speed',
        'duration',
        'points_count',
        'device_info',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'device_info' => 'array',
        'total_distance' => 'float',
        'max_speed' => 'float',
        'avg_speed' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(LocationPoint::class, 'session_id', 'session_id');
    }
}
