<?php

namespace App\Rules;

interface Rule
{
    public function validate($items, $subTotal, $customer, $value): bool;
}
