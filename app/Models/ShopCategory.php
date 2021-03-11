<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *      @OA\Xml(name="ShopCategory"),
 *      @OA\Property(property="name", type="string", example="ShopCategory Name"),
 *      @OA\Property(property="name_mm", type="string", example="ShopCategory အမည်"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ShopCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_mm',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function shopSubCategories()
    {
        return $this->hasMany(ShopSubCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_shop_category_map');
    }
}