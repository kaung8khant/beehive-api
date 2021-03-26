<?php

namespace App\Models;

use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
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
        return $this->status()->latest()->first()->status;
    }

    public function getTotalAmountAttribute()
    {
        $orderItems = $this->items;
        $totalAmount = 0;

        foreach ($orderItems as $item) {
            $amount = $item->amount + $item->tax + $item->discount;
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    public function contact()
    {
        return $this->hasOne(ShopOrderContact::class);
    }
    public function status()
    {
        return $this->hasOne(ShopOrderStatus::class);
    }
    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
