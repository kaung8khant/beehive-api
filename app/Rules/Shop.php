<?php

namespace App\Rules;

use App\Models\Product;

class Shop implements Rule
{
    private $usage;

    public function __construct($usage)
    {
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        if ($this->usage == "shop") {
            foreach ($items as $item) {
                $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();

                $shop_id = $product->shop->id;

                if (intval($value) == intval($shop_id)) {
                    return true;
                }
            }
        }

        return false;
    }
}
