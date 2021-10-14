<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class RestaurantBranch extends BaseModel
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
        // 'restaurant_id',
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
    ];

    protected $touches = ['restaurant'];

    public function toSearchableArray(): array
    {
        $array = $this->toArray();
        $array = $this->transform($array);
        $array['restaurant_name'] = $this->restaurant->name;
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
