<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Events\KeywordSearched;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        if ($request->filter) {
            $products = Product::search($request->filter)
                ->with([
                    'userToken' => AuthHelper::getCustomerSlug(),
                ])
                ->where('is_enable', 1)
                ->where('is_shop_enable', 1)
                ->paginate($request->size);

            KeywordSearched::dispatch(AuthHelper::getCustomerId(), $request->device_id, $request->filter, 'shop');
        } else {
            $products = Product::select(self::selectExclusiveColumns('products'))
                ->join('product_variants as pv', function ($query) {
                    $query->on('pv.id', '=', DB::raw('(SELECT id FROM product_variants WHERE product_variants.product_id = products.id ORDER BY price ASC LIMIT 1)'));
                })
                ->whereHas('shop', function ($query) {
                    $query->where('is_enable', 1);
                })
                ->where('products.is_enable', 1)
                ->whereNotNull('pv.price')
                ->orderBy('search_index', 'desc')
                ->orderBy('shop_sub_category_id', 'asc')
                ->orderBy('id', 'desc')
                ->paginate($request->size);
        }

        $imageFilteredProducts = $this->optimizeProducts($products);
        return $this->generateProductResponse($imageFilteredProducts, 200, 'array', $products->lastPage(), true);
    }

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

    public function getByShopSubCategory(ShopSubCategory $shopSubCategory)
    {
        $products = Product::where('shop_sub_category_id', $shopSubCategory->id)
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(request('size'));

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

    private static function selectExclusiveColumns()
    {
        return collect(Schema::getColumnListing('products'))->map(function ($column) {
            if (!in_array($column, ['description', 'variants', 'created_by', 'updated_by', 'created_at', 'updated_at'])) {
                return 'products.' . $column;
            }
        })->filter()->values()->toArray();
    }
}
