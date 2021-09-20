<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'restaurant_order_id',
        'menu_id',
        'restaurant_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'is_deleted' => 'boolean',
        'variations' => 'array',
        'variant' => 'array',
        'toppings' => 'array',
        'options' => 'array',
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
