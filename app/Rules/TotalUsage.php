<?php

namespace App\Rules;

use App\Models\RestaurantOrder;
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
        $shopOrder = ShopOrder::where('promocode_id', $this->promocode->id)
            ->where('order_status', '<>', 'cancelled')
            ->get();
        $restaurantOrder = RestaurantOrder::where('promocode_id', $this->promocode->id)
            ->where('order_status', '<>', 'cancelled')
            ->get();

        $orderCount = count($shopOrder) + count($restaurantOrder);
        return $orderCount < $value;
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
