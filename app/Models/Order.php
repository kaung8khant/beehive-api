<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      @OA\Xml(name="Order"),
 *      @OA\Property(property="customer_slug", type="string", example="D16AAF"),
 *      @OA\Property(property="order_date", type="string", example="2021-02-19"),
 *      @OA\Property(property="order_type", type="string", example="shop"),
 *      @OA\Property(property="special_instruction", type="string", example="special_instruction"),
 *      @OA\Property(property="payment_mode", type="string", example="CBPay"),
 *      @OA\Property(property="delivery_mode", type="string", example="delivery"),
 *      @OA\Property(property="rating", type="string", example="1"),
 *      @OA\Property(property="slug", type="string", readOnly=true)
 * )
 */
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

    public function orderContact()
    {
        return $this->hasOne(OrderContact::class);
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function getOrderStatusAttribute()
    {
        return $this->orderStatuses()->latest()->first()->status;
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