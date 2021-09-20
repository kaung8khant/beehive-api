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
                if ($value === $menu->restaurant->slug) {
                    return true;
                }
            }
        }
        return false;
    }

    public function validateItem($item, $value): bool
    {
        if ($this->usage == "restaurant") {
            $menu = MenuModel::where('slug', $item['slug'])->with('restaurant')->firstOrFail();
            if ($value == $menu->restaurant->slug) {
                return true;
            }
        }
        return false;
    }
}
