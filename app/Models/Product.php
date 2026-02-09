<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_url',
        'restaurant_id',
        'branch_id',
        'category_id',
        'is_active',
        'stock_alert',
    ];
    protected $casts = [
        'price' => 'float',
    ];
    public function getImageUrlAttribute($value)
    {
        return $value ? url($value) : null;
    }
    public function setImageUrlAttribute($value)
    {
        $this->attributes['image_url'] = $value ? str_replace(url('/'), '', $value) : null;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
