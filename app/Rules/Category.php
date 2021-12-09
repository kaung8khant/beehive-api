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
                    if (is_array($value)) {
                        if (in_array($category_slug, $value)) {
                            return true;
                        }
                    } else if ($value === $category_slug) {
                        return true;
                    }
                }
            }
        } elseif ($this->usage == "restaurant") {
            foreach ($items as $item) {
                $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();

                if ($menu) {
                    $category_slug = $menu->restaurantCategory->slug;
                    if (is_array($value)) {

                        if (in_array($category_slug, $value)) {
                            return true;
                        }
                    } else if ($value === $category_slug) {
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
            $category_slug = $product->shopCategory->slug;
            if (is_array($value)) {
                if (in_array($category_slug, $value)) {
                    return true;
                }
            } else if ($value === $product->shopCategory->slug) {
                return true;
            }
        } elseif ($this->usage == "restaurant") {
            $menu = Menu::where('slug', $item['slug'])->with('restaurantCategory')->first();
            $category_slug = $menu->restaurantCategory->slug;
            if (is_array($value)) {

                if (in_array($category_slug, $value)) {
                    return true;
                }
            } else if ($value == $menu->restaurantCategory->slug) {
                return true;
            }
        }
    }
}
