<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_order_id',
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }
}
