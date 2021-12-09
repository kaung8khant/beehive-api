<?php

namespace App\Rules;

use App\Models\Product;

class Shop implements Rule
{
    private $promocode;
    private $usage;

    public function __construct($promocode, $usage)
    {
        $this->promocode = $promocode;
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        if ($this->usage == "shop") {
            foreach ($items as $item) {
                $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();

                if (is_array($value)) {
                    if (in_array($product->shop->slug, $value)) {
                        return true;
                    }
                } else if ($value === $product->shop->slug) {
                    return true;
                }
            }
        }

        return false;
    }

    public function validateItem($item, $value): bool
    {
        if ($this->usage == "shop") {
            $product = Product::where('slug', $item['slug'])->with('shop')->firstOrFail();
            if (is_array($value)) {

                if (in_array($product->shop->slug, $value)) {
                    return true;
                }
            } else if ($value == $product->shop->slug) {
                return true;
            }
        }
        return false;
    }
}
