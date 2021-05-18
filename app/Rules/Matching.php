<?php

namespace App\Rules;

use Carbon\Carbon;

class Matching
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        switch ($value) {
            case 'dob':
                $result = $this->getValueFromModel('dob');
                $result = $result[0]->datae_of_birth;

                return Carbon::parse($result)->startOfDay() == Carbon::now()->startOfDay();
                break;
            case 'dob':
                $result = $this->getValueFromModel('new_customer_shop');
                $result2 = $this->getValueFromModel('new_customer_restaurant');
                return count($result) + count($result2) == 0;
                break;
        }

    }
}
