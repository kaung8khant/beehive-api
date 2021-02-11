<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

    protected $fillable= ['name','name_mm', 'slug','city_id'];

    /**
     * Get the city that owns the township.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

<<<<<<< HEAD
    public function shop_branches()
    {
        return $this->hasMany(ShopBranch::class);
=======
    public function restaurant_branches()
    {
        return $this->hasMany(RestaurantBranch::class);
>>>>>>> d235fc1e28bf61d232a6f0638f8eaa6e3416ef86
    }
}
