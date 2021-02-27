<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
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

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
