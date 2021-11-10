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

    public function shopCategories()
    {
        return $this->hasMany(ShopCategory::class);
    }
}
