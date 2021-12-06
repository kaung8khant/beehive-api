<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Product extends BaseModel
{
    use HasFactory, Searchable;

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
        'variants' => 'array',
    ];

    protected $appends = ['code', 'rating', 'images', 'covers'];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['id'] = $this->id;
        $array['shop_id'] = $this->shop ? $this->shop->id : null;
        $array['brand_id'] = $this->brand ? $this->brand->id : null;
        $array['shop_category_id'] = $this->shopCategory ? $this->shopCategory->id : null;
        $array['shop_sub_category_id'] = $this->shopSubCategory ? $this->shopSubCategory->id : null;

        $array['shop_name'] = $this->shop ? $this->shop->name : null;
        $array['brand_name'] = $this->brand ? $this->brand->name : null;
        $array['shop_category_name'] = $this->shopCategory ? $this->shopCategory->name : null;
        $array['shop_sub_category_name'] = $this->shopSubCategory ? $this->shopSubCategory->name : null;

        $array['is_shop_enable'] = $this->shop ? $this->shop->is_enable : null;
        $array['available_tags'] = $this->shop ? $this->shop->availableTags->pluck('name') : null;
        return $array;
    }

    public function getPriceAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->price;
        }

        return $value;
    }

    public function getTaxAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->tax;
        }

        return $value;
    }

    public function getDiscountAttribute($value)
    {
        if ($this->cheapestVariant()) {
            return $this->cheapestVariant()->discount;
        }

        return $value;
    }

    private function cheapestVariant()
    {
        return $this->productVariants()->where('is_enable', 1)->orderBy('price', 'asc')->first();
    }

    public function getCodeAttribute()
    {
        $mainCategoryCode = $this->shopCategory->shopMainCategory ? $this->shopCategory->shopMainCategory->code ?? '00' : '00';
        $shopCategoryCode = $this->shopCategory->code ?? '000';
        $subCategoryCode = $this->shopSubCategory->code ?? '00';
        $brandCode = $this->brand ? $this->brand->code ?? '0000' : '0000';

        return $mainCategoryCode . '-' . $shopCategoryCode . '-' . $subCategoryCode . '-' . $brandCode . '-' . sprintf('%04d', $this->id);
    }

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
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    public function getCoversAttribute()
    {
        return File::where('source', 'products')
            ->where('source_id', $this->id)
            ->where('type', 'cover')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
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

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
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
