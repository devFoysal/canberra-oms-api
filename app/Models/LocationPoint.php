<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'point_id',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'heading',
        'battery_level',
        'network_type',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'altitude' => 'float',
        'speed' => 'float',
        'heading' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LocationSession::class, 'session_id', 'session_id');
    }
}
