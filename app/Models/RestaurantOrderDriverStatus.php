<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantOrderDriverStatus extends Model
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
        return $this->belongsTo(RestaurantOrderDriver::class);
    }
}
