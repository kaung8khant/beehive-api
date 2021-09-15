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
            $menu = MenuModel::whereIn('slug', array_column($items, 'slug'))->pluck('slug');
            if ($menu == collect($value)) {
                return true;
            }
        }
        return false;
    }
}
