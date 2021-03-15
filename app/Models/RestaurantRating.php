<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'target_type',
        'source_id',
        'source_type',
        'rating',
        'review',
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
