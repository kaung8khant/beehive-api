<?php

namespace App\Rules;

class ExactDate implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
