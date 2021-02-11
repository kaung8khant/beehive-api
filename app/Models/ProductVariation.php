<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;
    protected $fillable = ['slug','name','description','product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_variation_value()
    {
        return $this->hasMany(ProductVariationValue::class);
    }
}
