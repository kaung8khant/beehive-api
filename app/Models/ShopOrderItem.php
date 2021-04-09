<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'quantity',
        'amount',
        'tax',
        'discount',
        'variations',
        'shop',
        'is_deleted',
        'shop_order_vendor_id',
        'product_id',
        'shop_id',
    ];

    protected $hidden = [
        'id',
        'is_deleted',
        'shop_order_vendor_id',
        'product_id',
        'shop_id',
        'created_at',
        'updated_at',
        'pivot',

    ];

    protected $casts = [
        'shop' => 'object',
        'variations' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(ShopOrderVendor::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
