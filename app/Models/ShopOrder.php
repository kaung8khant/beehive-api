<?php

namespace App\Models;

use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'customer_id',
        'order_date',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
        'promocode_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'promocode' => 'object',
    ];

    protected $appends = ['order_status', 'total_amount'];

    public function getOrderStatusAttribute()
    {
        $result = "pickUp";

        $items = collect($this->items->filter(function ($value) {
            return $value->status !== "onRoute" && $value->status !== "delivered" && $value->status !== "cancelled";
        })->mode('status'));

        if (count($items) > 0) {

            //get vendor status for each item and compare
            if ($items->contains('preparing')) {
                $result = 'preparing';
            }
            if ($items->contains('pending')) {
                $result = 'pending';
            }

        } else {

            //admin control the status after pickUp
            $items = $this->items->mode('status');
            $result = $items[0];

        }

        return $result;
    }

    public function getTotalAmountAttribute()
    {
        $orderItems = $this->items;
        $totalAmount = 0;

        foreach ($orderItems as $item) {
            $amount = $item->amount + $item->tax - $item->discount;
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    public function contact()
    {
        return $this->hasOne(ShopOrderContact::class);
    }

    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
