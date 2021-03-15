<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopOrder;

class ShopOrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_order_id',
        'status',
        'created_by',
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
