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
        'is_official',
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
        'is_official' => 'boolean',
    ];

    public function restaurant_tags()
    {
        return $this->belongsToMany(RestaurantTag::class, 'tag_restaurant');
    }

    public function restaurant_categories()
    {
        return $this->belongsToMany(RestaurantCategory::class, 'category_restaurant');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function restaurantBranch()
    {
        return $this->hasMany(RestaurantBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_restaurant');
    }
}
