<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'customer_id',
        'order_date',
        'order_type',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
        'rating',
    ];

    protected $hidden = [
        'id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    protected $appends = array('order_status');

    public function order_contact()
    {
        return $this->hasOne(OrderContact::class);
    }

    public function order_statuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function getOrderStatusAttribute()
    {
        return $this->order_statuses()->latest()->first()->status;
    }

    // public function getOrderTotalAttribute()
    // {
    //     $total=0;
    //     foreach ($this->order_items() as $item) {
    //         $total=($item->amount * $item->quantity)-($item->discount + ($item->amount $item->tax);
    //     }
    //     return $total;
    // }
}
