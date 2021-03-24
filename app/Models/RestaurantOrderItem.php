<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_name',
        'quantity',
        'amount',
        'tax',
        'discount',
        'is_deleted',
        'variations',
        'toppings',
        'restaurant_order_id',
        'menu_id',
    ];

    protected $hidden = [
        'id',
        'restaurant_order_id',
        'menu_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'variations' => AsArrayObject::class,
        'toppings' => AsArrayObject::class,
    ];

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }
}
