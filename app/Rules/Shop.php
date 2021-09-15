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

                $shop_slug = $product->shop->slug;

                if ($value === $shop_slug) {
                    return true;
                }
            }
        }

        return false;
    }
}
