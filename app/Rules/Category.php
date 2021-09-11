<?php

namespace App\Rules;

use App\Models\Menu;
use App\Models\Product;

class Category implements Rule
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
                $product = Product::where('slug', $item['slug'])->with('shopCategory')->first();

                if ($product) {
                    $category_id = $product->shopCategory->id;

                    if (intval($value) == intval($category_id)) {
                        return true;
                    }
                }
            }
        } else if ($this->usage == "restaurant") {
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

    public function validateItem($item, $value): bool
    {
        if ($this->usage == "shop") {
            $product = Product::where('slug', $item['slug'])->with('shopCategory')->firstOrFail();

            $category_id = $product->shopCategory->id;
            return intval($value) == intval($category_id);
        } else if ($this->usage == "restaurant") {
            $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();

            $category_id = $menu->restaurantCategory->id;
            return intval($value) == intval($category_id);
        }
    }
}
