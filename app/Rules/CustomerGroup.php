<?php

namespace App\Rules;

class CustomerGroup implements Rule
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        foreach ($customer->customerGroups as $customerGroup) {
            if ($customerGroup->slug === $value) {
                return true;
            }
        }
        return false;
    }

    public function validateItem($item, $value): bool
    {
        return false;
    }
}
