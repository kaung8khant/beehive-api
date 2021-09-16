<?php

namespace App\Rules;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
            case 'new_customer':
                $result = $this->getValueFromModel('new_customer_shop');
                $result2 = $this->getValueFromModel('new_customer_restaurant');

                return count($result) + count($result2) == 0;
                break;
        }
    }

    private function getValueFromModel($value)
    {
        $field = config('promo.' . $value);

        if ($field['value'] === 'auth') {
            $field['value'] = Auth::user()->id;
        }

        $data = $field['model']::where($field['field'], $field['condition'], $field['value'])->get();

        return $data;
    }
}
