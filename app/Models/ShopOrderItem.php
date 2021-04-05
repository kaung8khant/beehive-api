<?php

namespace App\Models;

use App\Models\ShopOrder;
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
        'shop_order_id',
        'product_id',
        'shop_id',
    ];

    protected $hidden = [
        'id',
        'is_deleted',
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

    protected $appends = ['status'];

    public function getStatusAttribute()
    {
        return $this->status()->latest()->first()->status;
    }

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
    public function status()
    {
        return $this->hasOne(ShopOrderStatus::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

}
