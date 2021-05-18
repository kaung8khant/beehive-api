<?php

namespace App\Rules;

class Menu implements Rule
{

    public function validate($items, $subTotal, $customer, $value): bool
    {

        return true;

    }

}
