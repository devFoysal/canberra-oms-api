<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;

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
                $date = date('Ymd');

                // Find last order created today
                $lastOrder = self::whereDate('created_at', today())
                    ->latest('id')
                    ->value('order_id');

                $sequence = 1;
                if ($lastOrder) {
                    $lastSequence = (int)substr($lastOrder, -3); // last 3 digits
                    $sequence = $lastSequence + 1;
                }

                $sequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

                $order->order_id = "ORD-{$date}-{$sequence}";
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
}
