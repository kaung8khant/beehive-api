<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable =["slug","name","name_mm","description","description_mm","price"];

    public function restaurants()
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
