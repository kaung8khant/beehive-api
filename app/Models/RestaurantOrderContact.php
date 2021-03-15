<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'phone_number',
        'house_number',
        'floor',
        'street_name',
        'latitude',
        'longitude',
        'restaurant_order_id',
    ];

    protected $hidden = [
        'id',
        'restaurant_order_id',
        'created_at',
        'updated_at',
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }
}
