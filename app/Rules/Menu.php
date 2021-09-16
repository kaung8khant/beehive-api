<?php

namespace App\Rules;

use App\Models\Menu as MenuModel;

class Menu implements Rule
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
                $menu = MenuModel::where('slug', $item['slug'])->firstOrFail();

                $menu_slug = $menu->slug;
                if ($value === $menu_slug) {
                    return true;
                }
            }
        }
        return false;
    }
}
