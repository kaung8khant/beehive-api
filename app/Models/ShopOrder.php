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
        'promocode_amount' => 'float',
        'commission' => 'float',
    ];

    protected $appends = ['invoice_id', 'amount', 'tax', 'discount', 'total_amount', 'driver_status', 'invoice_date'];

    public function getInvoiceIdAttribute()
    {
        return 'BHS' . sprintf('%08d', $this->id);
    }

    public function getAmountAttribute()
    {
        $vendors = $this->vendors;
        $amount = 0;

        foreach ($vendors as $vendor) {
            foreach ($vendor->items as $item) {
                $amount += $item->amount * $item->quantity;
            }
        }

        return $amount;
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

        $totalAmount = $totalAmount - $this->promocode_amount;
        return $totalAmount < 0 ? 0 : $totalAmount;
    }
    public function getDriverStatusAttribute()
    {
        $orderDriver = ShopOrderDriver::where('shop_order_id', $this->id)->latest()->first();

        if (!empty($orderDriver)) {
            $driverStatus = ShopOrderDriverStatus::where('shop_order_driver_id', $orderDriver->id)->latest()->value('status');
        } else {
            $driverStatus = null;
        }
        return $driverStatus;
    }

    public function getInvoiceDateAttribute()
    {
        $vendorIds = ShopOrderVendor::whereHas('shopOrder', function ($query) {
            $query->where('shop_order_id', $this->id);
        })->pluck('id')->toArray();

        $orderStatus = ShopOrderStatus::whereIn('status', ['pickUp', 'delivered'])
            ->whereHas('vendor', function ($query) use ($vendorIds) {
                $query->whereIn('id', $vendorIds);
            })
            ->latest('created_at')
            ->first();

        $invoiceDate = $orderStatus ? $orderStatus->created_at : null;
        return $invoiceDate;
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
