<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderContact extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'restaurant_order_id',
        'township_id',
        'created_at',
        'updated_at',
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function township()
    {
        return $this->belongsTo(Township::class);
    }
}
