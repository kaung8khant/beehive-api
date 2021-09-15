<?php

namespace App\Rules;

use App\Models\Menu as MenuModel;

class Restaurant implements Rule
{
    private $usage;

    public function __construct($usage)
    {
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        if ($this->usage == 'restaurant') {
            foreach ($items as $item) {
                $menu = MenuModel::where('slug', $item['slug'])->with('restaurant')->firstOrFail();

                $restaurant_id = $menu->restaurant->id;

                if (intval($value) == intval($restaurant_id)) {
                    return true;
                }
            }
        }
        return false;
    }
}
