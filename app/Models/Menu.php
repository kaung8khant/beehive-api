<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'description',
        'description_mm',
        'price',
        'restaurant_id',
        'restaurant_category_id',
        'is_enable',
    ];

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
        return $this->belongsToMany(RestaurantBranch::class, 'restaurant_branch_menu');
    }
}
