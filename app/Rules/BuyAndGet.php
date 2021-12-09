<?php

namespace App\Rules;

use App\Models\Product as ProductModel;

class BuyAndGet implements Rule
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
        $valid = false;
        foreach ($items as $item) {
            if ($this->validateItem($item, $value)) {
                $valid = true;
                break;
            }
        }

        return $valid;
    }

    public function validateItem($item, $value): bool
    {

        if ($item['quantity'] % 2 == 0 && $item['quantity'] > 0 && $item['quantity'] / 2 >= $value) {
            return true;
        }
        return false;
    }
}
