<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Product Variation"),

 *      @OA\Property(property="product_variations", type="array",
 *      @OA\Items(type="object",
 *      @OA\Property(property="name", type="string", example="Variation Name"),
 *      @OA\Property(property="product_variation_values", type="array",
 *      @OA\Items(type="object",
 *      @OA\Property(property="value", type="string", example="value"),
 *      @OA\Property(property="price", type="decimal",example=0.00),
 *      @OA\Property(property="slug", type="string", readOnly=true),
 *      ),
 *      ),
 *      ),
 *      ),
 *      @OA\Property(property="product_slug", type="string", example="Product Slug"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ProductVariation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'product_id',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariationValues()
    {
        return $this->hasMany(ProductVariationValue::class);
    }
}
