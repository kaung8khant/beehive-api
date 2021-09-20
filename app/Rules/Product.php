<?php

namespace App\Rules;

use App\Models\Product as ProductModel;

class Product implements Rule
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
        if ($this->usage == 'shop') {
            foreach ($items as $item) {
                if ($value === $item['slug']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function validateItem($item, $value): bool
    {
        if ($this->usage == "shop") {
            return $item['slug'] == $value;
        }
        return false;
    }
}
