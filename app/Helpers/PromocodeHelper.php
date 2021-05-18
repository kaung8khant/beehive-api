<?php

namespace App\Helpers;

use App\Exceptions\BadRequestException;
use App\Models\Promocode;
use App\Models\ShopOrder;
use Illuminate\Support\Carbon;

trait PromocodeHelper
{
    public function __construct()
    {
        // if (Auth::guard('customers')->check()) {
        //     $this->customer_id = Auth::guard('customers')->user()->id;
        // }
    }

    public static function validatePromocodeRules($promocode, $orderItem, $subTotal, $customer)
    {
        foreach ($promocode['rules'] as $data) {
            $_class = '\App\Rules\\' . str_replace('_', '', ucwords($data['data_type'], '_'));
            $rule = new $_class($promocode);
            $value = $rule->validate($orderItem, $subTotal, $customer, $data['value']);
            if (!$value) {
                throw new BadRequestException("Invalid promocode.", 400);
            }
        }
    }

    public static function calculatePromocodeAmount($promocode, $orderItem, $subTotal)
    {

        $isItemRule = false;
        foreach ($promocode->rules as $rule) {
            if ($rule === "shop" || $rule === "brand" || $rule === "menu" || $rule === "category") {
                $isItemRule = true;
            }
        }

        $total = $isItemRule ? ($orderItem->price - $orderItem->discount * $orderItem->quantity) : $subTotal;

        if ($promocode->type === 'fix') {
            return $promocode->amount;
        } else {
            return $total * $promocode->amount * 0.01;
        }
    }

    public static function validatePromocodeUsage($promocode, $usage)
    {
        if ($promocode->usage === 'both' || $usage == $promocode->usage) {
            return true;
        }
        throw new BadRequestException("Invalid promocode usage.", 400);
    }

    public function validatePromo($slug, $customerId, $usage)
    {
        $promo = $this->getPromo($slug);
        if ($promo->usage === 'both' || $usage == $promo->usage) {
            $this->customerId = $customerId;
            $this->promoId = $promo->id;
            return $this->validateRule($promo->rules);
        }
        return false;
    }

    public function validateRule($rules)
    {
        if (sizeof($rules) == 0) {
            return true;
        }
        $returnValue = false;

        foreach ($rules as $rule) {
            if (strpos($rule->data_type, 'date') !== false) {
                $returnValue = $this->compareValue($rule->data_type, Carbon::parse($rule->value), Carbon::now());
            } else {
                $returnValue = $this->compareValue($rule->data_type, $rule->value);
            }
        }

        return $returnValue;
    }

    public function calculateDiscount($price, $id)
    {
        $promo = Promocode::with('rules')->where('id', $id)->first();

        if ($promo->type === 'fix') {
            return $promo->amount;
        } else {
            return $price * $promo->amount / 100;
        }
    }

    public static function getPercentage($price, $id)
    {
        $promo = Promocode::with('rules')->where('id', $id)->first();
        if ($promo->type === 'fix') {
            return ($promo->amount / $price) * 100;
        } else {
            return $promo->amount;
        }
    }

    private function getPromo($slug)
    {
        return Promocode::with('rules')->where('slug', $slug)->firstOrFail();
    }

    private function getValueFromModel($value)
    {
        $field = config('promo.' . $value);

        if ($field['value'] === 'auth') {
            $field['value'] = $this->customerId;
        }

        $data = $field['model']::where($field['field'], $field['condition'], $field['value'])->get();

        return $data;
    }

    private function compareValue($rule, $value, $compareValue = null)
    {
        if ($rule === 'exact_date') {
            return $value->startOfDay() == $compareValue->startOfDay();
        } elseif ($rule === 'after_date') {
            return $compareValue >= $value; //current date greater than value (after value date)
        } elseif ($rule === 'before_date') {
            return $compareValue <= $value; //current date less than value (before value date)
        } elseif ($rule === 'total_usage') {
            $promo = Promocode::with('rules')->where('id', $this->promoId)->firstOrFail();
            $shopOrder = ShopOrder::where('promocode', $promo->id)->get();
            return count($shopOrder) < $value;
        } elseif ($rule === 'per_user_usage') {
            $promo = Promocode::with('rules')->where('id', $this->promoId)->firstOrFail();
            $shopOrder = ShopOrder::where('promocode', $promo->id)->where('customer_id', $this->customerId)->get();
            return count($shopOrder) < $value;
        } elseif ($rule === 'matching') {
            if ($value === 'dob') {
                $result = $this->getValueFromModel('dob');
                $result = $result[0]->datae_of_birth;

                return Carbon::parse($result)->startOfDay() == Carbon::now()->startOfDay();
            } elseif ($value === 'new_customer') {
                $result = $this->getValueFromModel('new_customer_shop');
                $result2 = $this->getValueFromModel('new_customer_restaurant');
                return count($result) + count($result2) == 0;
            }

            return false;
        }
    }
}
