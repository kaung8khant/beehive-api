<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ResponseHelper;

    public function getNewArrivalsByShop(Request $request, Shop $shop)
    {
        $products = Product::where('shop_id', $shop->id)
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $this->optimizeProducts($products);
        return $this->generateProductResponse($imageFilteredProducts, 200, 'array', $products->lastPage(), true);
    }

    public function getByBrandAndCategory(Request $request, Brand $brand, ShopCategory $category)
    {
        $products = Product::where('brand_id', $brand->id)
            ->where('shop_category_id', $category->id)
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $this->optimizeProducts($products);
        return $this->generateProductResponse($imageFilteredProducts, 200, 'array', $products->lastPage(), true);
    }

    private function optimizeProducts($products)
    {
        $products->load(['shop' => function ($query) {
            $query->select('id', 'slug', 'name');
        }]);

        return $products->map(function ($product) {
            $product->makeHidden(['description', 'variants', 'created_by', 'updated_by', 'covers']);
            $product->shop->makeHidden(['rating', 'images', 'covers', 'first_order_date']);
            return $product->images->count() > 0 ? $product : null;
        })->filter()->values();
    }
}
