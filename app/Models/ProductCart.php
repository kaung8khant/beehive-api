<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'promo_amount' => 'float',
    ];

    public function productCartItems()
    {
        return $this->hasMany(ProductCartItem::class);
    }
}
