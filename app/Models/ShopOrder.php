<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopOrder extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [
        'id',
        'promocode_id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $casts = [
        'promocode' => 'object',
    ];

    protected $appends = ['invoice_id','amount','tax', 'discount',  'total_amount'];

    public function getInvoiceIdAttribute()
    {
        return 'BHS' . sprintf('%08d', $this->id);
    }

    public function getAmountAttribute()
    {
        $vendors = $this->vendors;
        $totalAmount = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $totalAmount += $item->amount  * $item->quantity;
            }
        }

        return $totalAmount - $this->promocode_amount;
    }

    public function getTaxAttribute()
    {
        $vendors = $this->vendors;
        $tax = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $tax += $item->tax * $item->quantity;
            }
        }

        return $tax;
    }

    public function getDiscountAttribute()
    {
        $vendors = $this->vendors;
        $discount = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $discount += $item->discount * $item->quantity;
            }
        }

        return $discount;
    }

    public function getTotalAmountAttribute()
    {
        $vendors = $this->vendors;
        $totalAmount = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $totalAmount += ($item->amount - $item->discount + $item->tax) * $item->quantity;
            }
        }

        return $totalAmount - $this->promocode_amount;
    }

    public function contact()
    {
        return $this->hasOne(ShopOrderContact::class);
    }

    public function vendors()
    {
        return $this->hasMany(ShopOrderVendor::class);
    }

    public function drivers()
    {
        return $this->hasMany(ShopOrderDriver::class);
    }
}
