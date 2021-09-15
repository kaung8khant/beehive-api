<?php

namespace App\Rules;

use App\Models\Product;

class Brand implements Rule
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
                $product = Product::where('slug', $item['slug'])->with('brand')->firstOrFail();

                $brand_slug = $product->brand->slug;
                if ($value === $brand_slug) {
                    return true;
                }
            }
        }
        return false;
    }
}
