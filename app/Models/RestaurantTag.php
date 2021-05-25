<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantTag extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_restaurant_tag_map');
    }
}
