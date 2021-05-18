<?php

namespace App\Rules;

use App\Models\Product;

class Brand implements Rule
{
    private $promocode;

    public function __construct($promocode)
    {
        $this->promocode = $promocode;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {

        if ($this->promocode['usage'] == "shop" || $this->promocode['usage'] == "both") {
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

        $product = Product::where('slug', $item['slug'])->with('brand')->firstOrFail();

        $brand_id = $product->brand->id;
        return intval($value) == intval($brand_id);

    }
}
