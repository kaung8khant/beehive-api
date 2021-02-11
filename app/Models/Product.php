<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'description',
        'description_mm',
        'price',
        'shop_id',
        'shop_category_id',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shop_category()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function product_variation()
    {
        return $this->hasMany(ProductVariation::class);
    }
}
