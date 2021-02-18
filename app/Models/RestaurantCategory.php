<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_mm',
        'slug',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'category_restaurant');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
