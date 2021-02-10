<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['slug','name','name_mm','description','description_mm','price',"shop_id"];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function ProductVariation()
    {
        return $this->hasMany(ProductVariation::class);
    }

}