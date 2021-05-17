<?php

namespace App\Rules;

class Matching implements Rule
{
    public function validate($items, $subTotal, $customer): bool
    {
        return true;
    }
}
