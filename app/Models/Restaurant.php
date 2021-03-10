<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'is_enable',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function availableTags()
    {
        return $this->belongsToMany(RestaurantTag::class, 'restaurant_restaurant_tag_map');
    }

    public function availableCategories()
    {
        return $this->belongsToMany(RestaurantCategory::class, 'restaurant_restaurant_category_map');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function restaurantBranches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_restaurant');
    }
}
