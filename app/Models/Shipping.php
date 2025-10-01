<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable =  ['tracking_number', 'estimated_delivery_date', 'delivery_date', 'note', 'order_id', 'customer_id', 'created_by', 'status'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipping) {
            if (!$shipping->tracking_number) {
                $lastShipping = self::latest('id')->value('tracking_number');

                $sequence = 1;
                if ($lastShipping) {
                    // Remove prefix before converting to int
                    $lastSequence = (int) str_replace('TRK-', '', $lastShipping);
                    $sequence = $lastSequence + 1;
                }

                $shipping->tracking_number = "TRK-{$sequence}";
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
