<?php

namespace App\Rules;

use App\Models\Menu as MenuModel;

class Restaurant implements Rule
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
        if ($this->usage == 'restaurant') {
            foreach ($items as $item) {
                $menu = MenuModel::where('slug', $item['slug'])->with('restaurant')->firstOrFail();
                $restaurant_slug = $menu->restaurant->slug;
                if ($value === $restaurant_slug) {
                    return true;
                }
            }
        }
        return false;
    }
}
