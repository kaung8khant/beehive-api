<?php

namespace App\Rules;

use Carbon\Carbon;

class BeforeDate implements Rule
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        return Carbon::now() <= Carbon::parse($value);
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
