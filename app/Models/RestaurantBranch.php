<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class RestaurantBranch extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'notify_numbers' => 'array',
        'free_delivery' => 'boolean',
        'pre_order' => 'boolean',
        'instant_order' => 'boolean',
        'extra_charges' => 'array',
    ];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['id'] = $this->id;
        $array['restaurant_id'] = $this->restaurant ? $this->restaurant->id : null;
        $array['restaurant_name'] = $this->restaurant ? $this->restaurant->name : null;
        $array['is_restaurant_enable'] = $this->restaurant ? $this->restaurant->is_enable : null;
        $array['available_tags'] = $this->restaurant ? $this->restaurant->availableTags->pluck('name') : null;
        $array['_geoloc'] = [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];

        return $array;
    }

    public function getIsAvailableAttribute()
    {
        return boolval($this->pivot->is_available);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function availableMenus()
    {
        return $this->belongsToMany(Menu::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users', 'id');
    }
}
