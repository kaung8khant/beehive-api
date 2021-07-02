<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        // 'id',
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
        'variant' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(ShopOrderVendor::class, 'shop_order_vendor_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
