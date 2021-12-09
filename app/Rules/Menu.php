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
        Log::info('Menu rule');
        if ($this->usage == 'restaurant') {
            foreach ($items as $item) {
                if (is_array($value)) {
                    Log::info($value);
                    Log::info($item['slug']);
                    if (in_array($item['slug'], $value)) {
                        return true;
                    }
                } else if ($value === $item['slug']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function validateItem($item, $value): bool
    {
        if ($this->usage == "restaurant") {
            if (is_array($value)) {
                if (in_array($item['slug'], $value)) {
                    return true;
                }
            } else if ($item['slug'] == $value) {
                return true;
            }
        }
        return false;
    }
}
