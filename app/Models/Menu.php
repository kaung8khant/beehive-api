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
    ];

    protected $hidden = [
        'id',
        'restaurant_id',
        'restaurant_category_id',
        'created_at',
        'updated_at',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurant_category()
    {
        return $this->belongsTo(RestaurantCategory::class);
    }

    public function menu_variations()
    {
        return $this->hasMany(MenuVariation::class);
    }

    public function menu_toppings()
    {
        return $this->hasMany(MenuTopping::class);
    }
}
