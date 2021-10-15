<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ShopTag extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_shop_tag_map');
    }
}
