<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *      @OA\Xml(name="ShopCategory"),
 *      @OA\Property(property="name", type="string", example="ShopCategory Name"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
class ShopCategory extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'shop_categories')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

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
