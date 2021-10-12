<?php

namespace App\Models;

use App\Models\ShopOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopOrderContact extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'township_id',
        'shop_order_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function getFloorAttribute()
    {
        return $this->value ? $this->value : '0';
    }

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
