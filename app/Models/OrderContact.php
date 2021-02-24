<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
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
        'order_id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
