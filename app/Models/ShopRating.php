<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'target_type',
        'source_id',
        'source_type',
        'rating',
        'review',
        'shop_order_id',
    ];

    protected $hidden = [
        'id',
        'shop_order_id',
        'created_at',
        'updated_at',
    ];

    public function shopOrder()
    {
        return $this->belongsTo(ShopOrder::class);
    }
}
