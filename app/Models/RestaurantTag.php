<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class RestaurantTag extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array['id'] = $this->id;
        return $array;
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_restaurant_tag_map');
    }
}
