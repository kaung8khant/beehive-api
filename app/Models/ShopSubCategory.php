<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="ShopSubCategory"),
 *      @OA\Property(property="name", type="string", example="name"),
 *      @OA\Property(property="shop_category_slug", type="string", example="E1367A"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ShopSubCategory extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

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
