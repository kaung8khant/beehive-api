<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_order_vendor_id',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function vendor()
    {
        return $this->belongsTo(ShopOrderVendor::class);
    }
}
