<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'suspended',
        'message',
        'suspended_at',
        'updated_by',
    ];

    protected $casts = [
        'suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];
}
