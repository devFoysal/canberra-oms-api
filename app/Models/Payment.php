<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'method',
        'amount',
        'currency',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    // Example accessor for convenience
    public function getProviderAttribute()
    {
        return $this->details['provider'] ?? null;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
