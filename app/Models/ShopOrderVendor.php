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

    protected $appends = ['status'];

    public function getStatusAttribute()
    {
        return $this->vendorstatus->last()->status;
    }
    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function items()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
    public function order()
    {
        return $this->belongsTo(ShopOrder::class);
    }
    public function vendorstatus()
    {
        return $this->hasMany(ShopOrderStatus::class);
    }
}
