<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['slug', 'name', 'name_mm', 'official', 'enable'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'enable' => 'boolean',
        'official' => 'boolean',
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

    public function restaurant_branches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'favorite_restaurant');
    }
}
