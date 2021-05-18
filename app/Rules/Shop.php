<?php

namespace App\Rules;

use App\Models\Product;

class Shop implements Rule
{
    public function validate($item, $subTotal, $customer, $value): bool
    {

        $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();

        $shop_id = $product->shop->id;

        return intval($value) == intval($shop_id);

    }
}
