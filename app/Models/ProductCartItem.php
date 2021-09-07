<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCartItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'product_cart_id',
        'product_id',
        'shop_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'product' => 'array',
    ];
}
