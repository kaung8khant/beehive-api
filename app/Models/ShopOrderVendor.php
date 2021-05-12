<?php

namespace App\Models;

use App\Models\ShopOrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderVendor extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'shop_order_id',
        'shop_id',
        'pivot',
    ];

    protected $casts = [
        'promocode' => 'object',
    ];

    protected $appends = ['total_amount'];

    public function getTotalAmountAttribute()
    {
        $totalAmount = 0;

        foreach ($this->items as $item) {
            $amount = $item->amount + $item->tax - $item->discount;
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
