<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_id',
        'restaurant_category_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menus')
            ->where('source_id', $this->id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function getIsAvailableAttribute()
    {
        return boolval($this->pivot->is_available);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantCategory()
    {
        return $this->belongsTo(RestaurantCategory::class);
    }

    public function menuVariations()
    {
        return $this->hasMany(MenuVariation::class);
    }

    public function menuToppings()
    {
        return $this->hasMany(MenuTopping::class);
    }

    public function restaurantBranches()
    {
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu_map')->withPivot('is_available');
    }
}
