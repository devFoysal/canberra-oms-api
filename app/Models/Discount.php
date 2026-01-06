<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        "type",
        "value",
        "order_id"
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
