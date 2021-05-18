<?php

namespace App\Rules;

use Carbon\Carbon;

class AfterDate implements Rule
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        return Carbon::now() >= Carbon::parse($value);
    }
}
