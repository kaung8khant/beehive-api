<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'slug',
        'order_date',
        'order_type',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
        'rating_status',
    ];

    public function order_contact()
    {
        return $this->hasOne(orderContact::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
