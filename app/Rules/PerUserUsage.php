<?php

namespace App\Rules;

class PerUserUsage implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
