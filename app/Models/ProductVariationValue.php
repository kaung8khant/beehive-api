<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Product Variation Value"),
 *      @OA\Property(property="value", type="string", example="value"),
 *      @OA\Property(property="price", type="decimal", example=0.00),
 *      @OA\Property(property="product_variation_slug", type="string", example="product_variation_slug"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ProductVariationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'value',
        'price',
        'product_variation_id',
    ];

    protected $hidden = [
        'id',
        'product_variation_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'product_variation_values')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
