<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantOrder extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'customer_id',
        'restaurant_id',
        'restaurant_branch_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'restaurant_branch_info' => AsArrayObject::class,
        // 'delivery_fee' => 'float',
        // 'promocode_amount' => 'float',
        // 'commission' => 'float',
    ];

    protected $appends = ['invoice_id', 'amount', 'tax', 'discount', 'total_amount', 'driver_status'];

    public function getInvoiceIdAttribute()
    {
        return 'BHR' . sprintf('%08d', $this->id);
    }

    public function getAmountAttribute()
    {
        $orderItems = $this->restaurantOrderItems;
        $amount = 0;

        foreach ($orderItems as $item) {
            $amount += $item->amount * $item->quantity;
        }

        return strval($amount);
    }

    public function getTaxAttribute()
    {
        $orderItems = $this->restaurantOrderItems;
        $tax = 0;

        foreach ($orderItems as $item) {
            $tax += $item->tax * $item->quantity;
        }

        return strval($tax);
    }

    public function getDiscountAttribute()
    {
        $orderItems = $this->restaurantOrderItems;
        $discount = 0;

        foreach ($orderItems as $item) {
            $discount += $item->discount * $item->quantity;
        }

        return strval($discount);
    }

    public function getTotalAmountAttribute()
    {
        $orderItems = $this->restaurantOrderItems;
        $totalAmount = 0;

        foreach ($orderItems as $item) {
            $amount = ($item->amount + $item->tax - $item->discount) * $item->quantity;
            $totalAmount += $amount;
        }

        return strval($totalAmount - $this->promocode_amount + $this->delivery_fee);
    }

    public function getDriverStatusAttribute()
    {
        $restaurantOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $this->id)->latest()->first();

        if (!empty($restaurantOrderDriver)) {
            $driverStatus = RestaurantOrderDriverStatus::where('restaurant_order_driver_id', $restaurantOrderDriver->id)->latest()->value('status');
        } else {
            $driverStatus = null;
        }

        return $driverStatus;
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

    public function drivers()
    {
        return $this->hasMany(RestaurantOrderDriver::class);
    }
}
