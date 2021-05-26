<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $with = ['productVariationValues'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariationValues()
    {
        return $this->hasMany(ProductVariationValue::class);
    }
}
