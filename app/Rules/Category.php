<?php

namespace App\Rules;

use App\Models\Product;

class Category implements Rule
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

                $product = Product::where('slug', $item['slug'])->with('shopCategory')->firstOrFail();

                $category_id = $product->shopCategory->id;
                if (intval($value) == intval($category_id)) {
                    return true;
                }

            }
        }
        return false;

    }
    public function validateItem($item, $value): bool
    {

        $product = Product::where('slug', $item['slug'])->with('shopCategory')->firstOrFail();

        $category_id = $product->shopCategory->id;
        return intval($value) == intval($category_id);

    }
}
