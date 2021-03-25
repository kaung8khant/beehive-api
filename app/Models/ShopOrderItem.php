<?php

namespace App\Models;

use App\Models\ShopOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_order_id',
        'product_id',
        'product_name',
        'quantity',
        'amount',
        'tax',
        'discount',
        'variations',
        'shop',

    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
        'is_deleted',
    ];

    protected $casts = [
        'shop' => 'object',
        'variations' => 'array',
    ];

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
