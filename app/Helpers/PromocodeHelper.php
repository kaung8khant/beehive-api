<?php

namespace App\Helpers;

use App\Models\Promocode;
use App\Models\ShopOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait PromocodeHelper
{
    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

    public function validatePromo($slug)
    {
        $promo = $this->getPromo($slug);
        return $this->validateRule($promo->rules, $promo->id);
    }

    public function validateRule($rules, $id)
    {
        $this->promo_id = $id;
        $returnvalue = false;
        foreach ($rules as $rule) {
            if (strpos($rule->data_type, "date") !== false) {
                $returnvalue = $this->compareValue($rule->data_type, Carbon::parse($rule->value), Carbon::now());
            } else {
                $returnvalue = $this->compareValue($rule->data_type, $rule->value);
            }
        }
        return $returnvalue;
    }

    public function calculateDiscount($price, $id)
    {
        $promo = Promocode::with('rules')->where('id', $id)->first();
        if ($promo->type === "fix") {
            return $promo->amount;
        } else {
            return $price * $promo->amount / 100;
        }
    }

    public static function getPercentage($price, $id)
    {
        $promo = Promocode::with('rules')->where('id', $id)->first();
        if ($promo->type === "fix") {
            return ($promo->amount / $price) * 100;
        } else {
            return $promo->amount;
        }
    }

    private function getPromo($slug)
    {
        return Promocode::with("rules")->where('slug', $slug)->firstOrFail();
    }

    private function getValueFromModel($value)
    {
        $field = config('promo.' . $value);
        if ($field['value'] === "auth") {
            $field['value'] = $this->customer_id;
        }
        $data = $field['model']::where($field['field'], $field['condition'], $field['value'])->get();

        return $data;
    }

    private function compareValue($rule, $value, $compareValue = null)
    {
        if ($rule === "exact_date") {
            return $value->startOfDay() == $compareValue->startOfDay();
        } else if ($rule === "after_date") {

            return $compareValue >= $value; //current date greater than value (after value date)

        } else if ($rule === "before_date") {

            return $compareValue <= $value; //current date less than value (before value date)

        } else if ($rule === "total_usage") {

            $promo = Promocode::with('rules')->where('id', $this->promo_id)->firstOrFail();
            $shopOrder = ShopOrder::where("promocode", $promo->id)->get();
            return count($shopOrder) < $value;
        } else if ($rule === "per_user_usage") {

            $promo = Promocode::with('rules')->where('id', $this->promo_id)->firstOrFail();
            $shopOrder = ShopOrder::where("promocode", $promo->id)->where('customer_id', Auth::guard('customers')->user()->id)->get();
            return count($shopOrder) < $value;
        } else if ($rule === "matching") {

            if ($value === "dob") {

                $result = $this->getValueFromModel("dob");
                $result = $result[0]->datae_of_birth;

                return Carbon::parse($result)->startOfDay() == Carbon::now()->startOfDay();
            } else if ($value === "new_customer") {

                $result = $this->getValueFromModel("new_customer_shop");
                $result2 = $this->getValueFromModel("new_customer_restaurant");
                return count($result) + count($result2) == 0;
            }

            return false;
        }
    }
}
