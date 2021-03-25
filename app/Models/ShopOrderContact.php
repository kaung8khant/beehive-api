<?php

namespace App\Models;

use App\Models\ShopOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_order_id',
        'customer_name',
        'phone_number',
        'house_number',
        'floor',
        'street_name',
        'latitude',
        'township_id',
        'longitude',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
    public function township()
    {
        return $this->belongsTo(Township::class);
    }
}
