<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'sku',
        'name',
        'thumbnail',
        'cover_image',
        'short_description',
        'description',
        'purchase_price',
        'sale_price',
        'stock',
        'slug',
        'category_id',
        'status',
        'created_at',
        'updated_at',
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
