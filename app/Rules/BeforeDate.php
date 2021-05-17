<?php

namespace App\Rules;

class BeforeDate implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
