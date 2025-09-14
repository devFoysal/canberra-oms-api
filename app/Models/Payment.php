<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'transition_number',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->transition_number) {
                $date = date('Ymd');

                // Find last transition number created today
                $lastPayment = self::whereDate('created_at', today())
                    ->latest('id')
                    ->value('transition_number');

                $sequence = 1;
                if ($lastPayment) {
                    $lastSequence = (int) substr($lastPayment, -3); // last 3 digits
                    $sequence = $lastSequence + 1;
                }

                $sequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

                $payment->transition_number = "TRN-{$date}-{$sequence}";
            }
        });
    }

    // Example accessor for convenience
    public function getProviderAttribute()
    {
        return $this->details['provider'] ?? null;
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
