<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Jobs\Algolia\UpdateProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ShopSubCategory extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'shop_category_id',
    ];

    protected $appends = ['images'];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateProduct::dispatch($uniqueKey, $model);
        });
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array['id'] = $this->id;
        $array['shop_category_id'] = $this->shopCategory ? $this->shopCategory->id : null;
        $array['shop_category_name'] = $this->shopCategory ? $this->shopCategory->name : null;
        return $array;
    }

    public function getImagesAttribute()
    {
        return File::where('source', 'shop_sub_categories')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    public function shopCategory()
    {
        return $this->belongsTo(ShopCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
