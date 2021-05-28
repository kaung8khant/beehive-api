<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_id',
        'shop_category_id',
        'shop_sub_category_id',
        'brand_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    protected $appends = ['rating', 'images', 'covers'];

    public function getRatingAttribute()
    {
        $rating = ShopRating::where('target_id', $this->id)
            ->where('target_type', 'product')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'products')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getCoversAttribute()
    {
        return File::where('source', 'products')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shopCategory()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function shopSubCategory()
    {
        return $this->belongsTo(ShopSubCategory::class);
    }

    public function productVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_product', 'customer_id');
    }

    public function shopOrder()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
