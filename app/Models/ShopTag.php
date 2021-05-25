<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopTag extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_shop_tag_map');
    }
}
