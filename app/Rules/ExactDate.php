<?php

namespace App\Rules;

use Carbon\Carbon;

class ExactDate implements Rule
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        return Carbon::now()->startOfDay() == Carbon::parse($value)->startOfDay();
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
