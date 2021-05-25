<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Township extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'city_id',
        'created_at',
        'updated_at',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function restaurantBranches()
    {
        return $this->hasMany(RestaurantBranch::class);
    }
}
