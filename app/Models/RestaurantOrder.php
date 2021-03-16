<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'order_date',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
        'customer_id',
        'restaurant_id',
        'restaurant_branch_id',
    ];

    protected $hidden = [
        'id',
        'customer_id',
        'restaurant_id',
        'restaurant_branch_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = array('order_status');

    public function getOrderStatusAttribute()
    {
        return $this->restaurantOrderStatuses()->latest()->first()->status;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantBranch()
    {
        return $this->belongsTo(RestaurantBranch::class);
    }

    public function restaurantOrderStatuses()
    {
        return $this->hasMany(RestaurantOrderStatus::class);
    }

    public function restaurantOrderContact()
    {
        return $this->hasOne(RestaurantOrderContact::class);
    }

    public function restaurantOrderItems()
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    public function restaurantRatings()
    {
        return $this->hasMany(RestaurantRating::class);
    }
}
