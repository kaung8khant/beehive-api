<?php

namespace App\Rules;

use App\Models\Product;

class Brand implements Rule
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
                $product = Product::where('slug', $item['slug'])->with('brand')->firstOrFail();
                $brand_id = $product->brand->id;

                if (intval($value) == intval($brand_id)) {
                    return true;
                }
            }
        }
        return false;
    }
}
