<?php

namespace App\Rules;

use App\Models\ShopOrder;

class TotalUsage implements Rule
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
        $shopOrder = ShopOrder::where('promocode', $this->promocode->id)->get();
        return count($shopOrder) < $value;
    }
}
