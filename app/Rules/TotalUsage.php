<?php

namespace App\Rules;

use App\Models\ShopOrder;

class TotalUsage implements Rule
{
    private $promocode;

    public function __construct($promocode)
    {
        $this->promocode = $promocode;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        $shopOrder = ShopOrder::where('promocode', $this->promocode->id)->get();
        return count($shopOrder) < $value;
    }
}
