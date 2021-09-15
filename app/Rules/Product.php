<?php

namespace App\Rules;

use App\Models\Product as ProductModel;

class Product implements Rule
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
        if ($this->usage == 'shop') {
            foreach ($items as $item) {
                $product = ProductModel::where('slug', $item['slug'])->firstOrFail();

                $product_slug = $product->slug;
                if ($value === $product_slug) {
                    return true;
                }
            }
        }
        return false;
    }
}
