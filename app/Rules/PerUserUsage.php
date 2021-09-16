<?php

namespace App\Rules;

use App\Models\Promocode;
use App\Models\RestaurantOrder;
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
        $shopOrder = ShopOrder::where('promocode_id', $this->promocode->id)->where('customer_id', $customer->id)->get();
        $restaurantOrder = RestaurantOrder::where('promocode_id', $this->promocode->id)->where('customer_id', $customer->id)->get();
        $orderCount = count($shopOrder) + count($restaurantOrder);
        return $orderCount < $value;
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
