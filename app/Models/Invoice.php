<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'order_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'customer_id',
        'created_by_id',
        'modified_by_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $date = date('Ymd');

                // Find last invoice created today
                $lastInvoice = self::whereDate('created_at', today())
                    ->latest('id')
                    ->value('invoice_number');

                $sequence = 1;
                if ($lastInvoice) {
                    $lastSequence = (int) substr($lastInvoice, -3); // last 3 digits
                    $sequence = $lastSequence + 1;
                }

                $sequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

                $invoice->invoice_number = "INV-{$date}-{$sequence}";
            }
        });
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
