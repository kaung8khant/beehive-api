<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderDriverStatus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'shop_order_driver_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    public function shopOrderDriver()
    {
        return $this->belongsTo(shopOrderDriver::class);
    }
}
