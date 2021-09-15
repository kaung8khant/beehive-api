<?php

namespace App\Rules;

use App\Models\Menu;
use App\Models\Product;

class Category implements Rule
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
                $product = Product::where('slug', $item['slug'])->with('shopCategory')->first();

                if ($product) {
                    $category_id = $product->shopCategory->id;

                    if (intval($value) == intval($category_id)) {
                        return true;
                    }
                }
            }
        } elseif ($this->usage == "restaurant") {
            foreach ($items as $item) {
                $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();

                if ($menu) {
                    $category_id = $menu->restaurantCategory->id;

                    if (intval($value) == intval($category_id)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
