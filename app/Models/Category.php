<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
        'image',
        'slug',
        'category_id',
        'status',
        'created_at',
        'updated_at',
    ];

    public function parent() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function children() {
        return $this->hasMany(Category::class, 'category_id');
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
}
