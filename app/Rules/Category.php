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
                    $category_slug = $product->shopCategory->slug;

                    if ($value === $category_slug) {
                        return true;
                    }
                }
            }
        } elseif ($this->usage == "restaurant") {
            foreach ($items as $item) {
                $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();

                if ($menu) {
                    $category_slug = $menu->restaurantCategory->slug;

                    if ($value === $category_slug) {
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
            return $value == $product->shopCategory->slug;
        } elseif ($this->usage == "restaurant") {
            $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();
            return $value == $menu->restaurantCategory->slug;
        }
    }
}
