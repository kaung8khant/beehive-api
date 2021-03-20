<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderItem;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'customer_id',
        'order_date',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    public function shopOrderContacts()
    {
        return $this->hasOne(ShopOrderContact::class);
    }
    public function shopOrderStatus()
    {
        return $this->hasOne(ShopOrderStatus::class);
    }
    public function shopOrderItems()
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
