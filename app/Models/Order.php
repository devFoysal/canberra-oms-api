<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use App\Models\Shipping;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'sales_rep_id',
        'subtotal',
        'tax',
        'total',
        'status',
        'invoice_number',
        'payment_status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_id) {
                $lastOrder = self::latest('id')->value('order_id');

                $sequence = 1;
                if ($lastOrder) {
                    // Remove prefix before converting to int
                    $lastSequence = (int) str_replace('ORD-', '', $lastOrder);
                    $sequence = $lastSequence + 1;
                }

                $order->order_id = "ORD-{$sequence}";
            }
        });
    }


    // Relationship to Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship to Sales Rep (User)
    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship to Order Items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'id', 'order_id');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'order_id', 'id');
    }
}
