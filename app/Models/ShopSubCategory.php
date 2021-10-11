<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ShopSubCategory extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
        'created_at',
        'updated_at',
        // 'shop_category_id',
    ];

    protected $touches = ['shopCategory'];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array = $this->transform($array);
        $array['shop_category_name'] = $this->shopCategory->name;
        return $array;
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
