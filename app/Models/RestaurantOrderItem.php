<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'restaurant_id',
    ];

    protected $hidden = [
        'id',
        'restaurant_order_id',
        'menu_id',
        'restaurant_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'variations' => AsArrayObject::class,
        'toppings' => AsArrayObject::class,
    ];

    protected $appends = ['images'];

    public function getImagesAttribute()
    {
        return File::where('source', 'menus')
            ->where('source_id', $this->menu_id)
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }
}
