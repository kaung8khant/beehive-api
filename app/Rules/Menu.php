<?php

namespace App\Rules;

use App\Models\Menu as MenuModel;

class Menu implements Rule
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
                $menu = MenuModel::where('slug', $item['slug'])->firstOrFail();

                $menu_id = $menu->id;
                if (intval($value) == intval($menu_id)) {
                    return true;
                }
            }
        }
        return false;
    }
}
