<?php

namespace App\Rules;

use App\Models\Product as ProductModel;

class Product implements Rule
{
    private $usage;

    public function __construct($usage)
    {
        $this->usage = $usage;
    }

    public function validate($items, $subTotal, $customer, $value): bool
    {
        if ($this->usage == 'shop') {
            foreach ($items as $item) {
                $product = ProductModel::where('slug', $item['slug'])->firstOrFail();

                $product_id = $product->id;
                if (intval($value) == intval($product_id)) {
                    return true;
                }
            }
        }
        return false;
    }
}
