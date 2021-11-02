<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Jobs\Algolia\UpdateProduct;
use App\Jobs\Algolia\UpdateShopSubCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ShopCategory extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $appends = ['images'];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateShopSubCategory::dispatch($uniqueKey, $model);

            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateProduct::dispatch($uniqueKey, $model);
        });
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array['id'] = $this->id;
        return $array;
    }

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
