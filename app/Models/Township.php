<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

<<<<<<< HEAD
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'name_mm',
        'slug', 'city_id',
        'restaurant_vendor_id',
    ];
=======
    protected $fillable= ['name','name_mm', 'slug','city_id'];
>>>>>>> feature/restaurant-branch

    /**
     * Get the city that owns the township.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function restaurant_branches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }
}
