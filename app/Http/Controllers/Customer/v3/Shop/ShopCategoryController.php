<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;

class ShopCategoryController extends Controller
{
    use ResponseHelper;

    public function getByBrand(Brand $brand)
    {
        $categoryIds = Product::where('brand_id', $brand->id)
            ->where('is_enable', 1)
            ->pluck('shop_category_id')
            ->unique()
            ->values()
            ->toArray();

        $categories = $this->getCategoriesByIds($categoryIds);
        return $this->generateResponse($categories, 200);
    }

    public function getByShop(Shop $shop)
    {
        $categoryIds = $categoryIds = Product::where('shop_id', $shop->id)
            ->where('is_enable', 1)
            ->pluck('shop_category_id')
            ->unique()
            ->values()
            ->toArray();

        $categories = $this->getCategoriesByIds($categoryIds);
        return $this->generateResponse($categories, 200);
    }

    private function getCategoriesByIds($categoryIds)
    {
        $categories = ShopCategory::whereIn('id', $categoryIds)
            ->orderBy('search_index', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $categories->makeHidden(['created_by', 'updated_by']);
        return $categories;
    }
}
