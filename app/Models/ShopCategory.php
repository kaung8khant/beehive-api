<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        $productId = Product::where('shop_category_id', $this->id)->inRandomOrder()->first();

        return File::where('source', 'shop_categories')
            ->where('source_id', $productId)
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
