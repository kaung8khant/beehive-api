<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopOrder;

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
}
