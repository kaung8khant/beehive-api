<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopOrderDriverStatus extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_order_driver_id',
        'status',
    ];

    public function shopOrderDriver()
    {
        return $this->belongsTo(shopOrderDriver::class);
    }
}