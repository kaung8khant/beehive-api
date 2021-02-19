<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_mm',
        'city_id',
    ];

    protected $hidden =  [
        'id',
        'created_at',
        'updated_at',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shop_branches()
    {
        return $this->hasMany(ShopBranch::class);
    }

    public function restaurant_branches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }
}
