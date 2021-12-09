<?php

namespace App\Rules;

class TotalAmount implements Rule
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
        if ($subTotal < $value) {
            return false;
        }
        return true;
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
