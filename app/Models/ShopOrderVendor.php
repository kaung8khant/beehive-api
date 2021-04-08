<?php

namespace App\Models;

use App\Models\ShopOrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'shop_order_id',
        'shop_id',
        'order_status',
    ];

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

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function order()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
