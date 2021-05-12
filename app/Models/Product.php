<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Product"),
 *      @OA\Property(property="name", type="string", example="Product Name"),
 *      @OA\Property(property="description", type="string", example="Description"),
 *      @OA\Property(property="price", type="decimal", example=0.00),
 *      @OA\Property(property="tax", type="integer", example=0),
 *      @OA\Property(property="shop_slug", type="string", example="shop_slug"),
 *      @OA\Property(property="shop_category_slug", type="string", example="shop_category_slug"),
 *      @OA\Property(property="shop_sub_category_slug", type="string", example="shop_sub_category_slug"),
 *      @OA\Property(property="brand_slug", type="string", example="brand_slug"),
 *      @OA\Property(property="is_enable", type="boolean", example="true"),
 *      @OA\Property(property="product_variations", type="array",
 *      @OA\Items(type="object",
 *      @OA\Property(property="name", type="string", example="Variation Name"),
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
