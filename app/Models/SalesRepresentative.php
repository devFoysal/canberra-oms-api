<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRepresentative extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'territory',
        'commission_rate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
