<?php

namespace App\Rules;

use App\Models\Product;

class Brand implements Rule
{

    public function __construct($promocode, $usage)
    {
        $this->promocode = $promocode;
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {

        if ($this->usage == "shop") {
            foreach ($items as $item) {

                $product = Product::where('slug', $item['slug'])->with('brand')->firstOrFail();

                $brand_id = $product->brand->id;
                if (intval($value) == intval($brand_id)) {
                    return true;
                }

            }
        }
        return false;

    }
    public function validateItem($item, $value): bool
    {
        if ($this->usage == "shop") {

            $product = Product::where('slug', $item['slug'])->with('brand')->firstOrFail();

            $brand_id = $product->brand->id;
            return intval($value) == intval($brand_id);
        }

    }
}
