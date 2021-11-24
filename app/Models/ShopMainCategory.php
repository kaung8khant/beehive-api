<?php

namespace App\Models;

use App\Helpers\StringHelper;
use App\Jobs\Algolia\UpdateShopCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ShopMainCategory extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['images'];

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $uniqueKey = StringHelper::generateUniqueSlug();
            UpdateShopCategory::dispatch($uniqueKey, $model);
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
        return File::where('source', 'shop_main_categories')
            ->where('source_id', $this->id)
            ->where('type', 'image')
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    public function shopCategories()
    {
        return $this->hasMany(ShopCategory::class);
    }
}
