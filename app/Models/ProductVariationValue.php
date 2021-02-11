<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationValue extends Model
{
    use HasFactory;
    protected $fillable = ['slug','name','value','price',"product_variation_id"];

    public function product_variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }


}
