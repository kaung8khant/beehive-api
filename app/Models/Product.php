<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'description',
        'description_mm',
        'price',
        'shop_id',
        'shop_category_id',
        'sub_category_id',
    ];

    protected $hidden = [
        'id',
        'shop_id',
        'shop_category_id',
        'sub_category_id',
        'created_at',
        'updated_at',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shop_category()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function sub_category()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function product_variation()
    {
        return $this->hasMany(ProductVariation::class);
    }
}
