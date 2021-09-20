<?php

namespace App\Rules;

interface Rule
{
    public function validate($items, $subTotal, $customer, $value): bool;

    public function validateItem($item, $value): bool;
}
