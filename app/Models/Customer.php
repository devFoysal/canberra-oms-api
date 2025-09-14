<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'avatar',
        'mobile_number',
        'shop_name',
        'address',
        'status',
        'created_by_id',
        'created_at',
        'updated_at',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function orders() {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
