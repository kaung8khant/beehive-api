<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="ShopSubCategory"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="name_mm", type="string", example="အမည်"),
 *      @OA\Property(property="shop_category_slug", type="string", example="E1367A"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */

class ShopSubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'shop_category_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'shop_category_id',
    ];

    public function shopCategory()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
