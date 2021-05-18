<?php

namespace App\Rules;

use App\Models\Product;

class Shop implements Rule
{
    public function validate($items, $subTotal, $customer, $value): bool
    {
        foreach ($items as $item) {
            $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();

            $shop_id = $product->shop->id;

            if (intval($value) == intval($shop_id)) {
                return true;
            }
        }
        return false;

    }
    public function validateItem($item, $value): bool
    {

        $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();

        $shop_id = $product->shop->id;

        return intval($value) == intval($shop_id);

    }
}
