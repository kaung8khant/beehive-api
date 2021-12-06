<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderDriver extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status()
    {
        return $this->hasMany(RestaurantOrderDriverStatus::class);
    }
}
