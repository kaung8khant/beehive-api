<?php

namespace App\Rules;

use App\Models\Promocode;
use App\Models\ShopOrder;

class PerUserUsage implements Rule
{
    private $promocode;
    private $usage;

    public function __construct($promocode, $usage)
    {
        $this->promocode = $promocode;
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        $promo = Promocode::with('rules')->where('id', $this->promocode->id)->firstOrFail();
        $shopOrder = ShopOrder::where('promocode_id', $promo->id)->where('customer_id', $customer->id)->get();
        return count($shopOrder) < $value;
    }
}
