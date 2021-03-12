<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Product"),
 *      @OA\Property(property="name", type="string", example="Product Name"),
 *      @OA\Property(property="name_mm", type="string", example="အမည်"),
 *      @OA\Property(property="description", type="string", example="Description"),
 *      @OA\Property(property="description_mm", type="string", example="ဖော်ပြချက်"),
 *      @OA\Property(property="price", type="decimal", example=0.00),
 *      @OA\Property(property="shop_slug", type="string", example="shop_slug"),
 *      @OA\Property(property="shop_category_slug", type="string", example="shop_category_slug"),
 *      @OA\Property(property="shop_sub_category_slug", type="string", example="shop_sub_category_slug"),
 *      @OA\Property(property="brand_slug", type="string", example="brand_slug"),
 *      @OA\Property(property="is_enable", type="boolean", example="true"),
 *      @OA\Property(property="product_variations", type="array",
 *      @OA\Items(type="object",
 *      @OA\Property(property="name", type="string", example="Variation Name"),
 *      @OA\Property(property="name_mm", type="string", example="အမည်"),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      @OA\Property(property="product_variation_values", type="array",
 *      @OA\Items(type="object",
 *      @OA\Property(property="value", type="string", example="value"),
 *      @OA\Property(property="price", type="decimal",example=0.00),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      ),
 *      ),
 *      ),
 *      ),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

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
        'shop_sub_category_id',
        'brand_id',
        'is_enable'
    ];

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

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return '';
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
}