<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_mm',
        'slug',
        'shop_category_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'shop_category_id',
    ];

    public function shop_category()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
