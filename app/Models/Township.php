<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;

    protected $fillable= ['name','name_mm', 'slug','city_id'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function restaurant_branches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }
}
