<?php

namespace App\Rules;

class TotalUsage implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
