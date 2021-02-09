<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name','name_mm', 'slug', 'shop_category_id'];

    public function shop_category()
    {
        return $this->belongsTo(ShopCategory::class);
    }
}
