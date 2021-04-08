<?php

namespace App\Models;

use App\Models\ShopOrderContact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'order_date',
        'special_instruction',
        'payment_mode',
        'delivery_mode',
        'order_status',
        'promocode_id',
        'customer_id',
    ];

    protected $hidden = [
        'id',
        'promocode_id',
        'customer_id',
        'created_at',
        'updated_at',
        'pivot',
        'vendors',
    ];

    protected $casts = [
        'promocode' => 'object',
    ];

    protected $appends = ['total_amount'];

    public function getTotalAmountAttribute()
    {
        $vendors = $this->vendors;
        $totalAmount = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $amount = $item->amount + $item->tax - $item->discount;
                $totalAmount += $amount;
            }
        }

        return $totalAmount;
    }

    public function contact()
    {
        return $this->hasOne(ShopOrderContact::class);
    }

    public function vendors()
    {
        return $this->hasMany(ShopOrderVendor::class);
    }
}
