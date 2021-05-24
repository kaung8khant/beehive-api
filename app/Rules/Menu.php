<?php

namespace App\Rules;

use App\Models\Menu as MenuModel;

class Menu implements Rule
{
    private $promocode;

    public function __construct($promocode, $usage)
    {
        $this->promocode = $promocode;
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {

        if ($this->usage == 'restaurant') {

            foreach ($items as $item) {

                $menu = MenuModel::where('slug', $item['slug'])->firstOrFail();

                $menu_id = $menu->id;
                if (intval($value) == intval($menu_id)) {
                    return true;
                }

            }
        }
        return false;

    }
    public function validateItem($item, $value): bool
    {
        if ($this->usage == "restaurant") {
            $menu = MenuModel::where('slug', $item['slug'])->firstOrFail();

            $menu_id = $menu->id;
            return intval($value) == intval($menu_id);
        }
        return false;

    }

}
