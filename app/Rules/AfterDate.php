<?php

namespace App\Rules;

class AfterDate implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
