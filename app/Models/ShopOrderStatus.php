<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_order_vendor_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function vendor()
    {
        return $this->belongsTo(ShopOrderVendor::class);
    }
}
