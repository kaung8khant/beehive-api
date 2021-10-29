<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\CacheHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\RestaurantOrder;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    use ResponseHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function test($slug)
    {
        $branch = RestaurantOrder::with('restaurantBranch')->where('slug', $slug)->first()->restaurant_branch_info;
        return response()->json($branch['longitude']);
    }

    public function index()
    {
        $shops = Shop::select('id', 'slug', 'name', 'opening_time', 'closing_time', 'is_official', 'is_enable')
            ->with('availableTags')
            ->withCount(['products' => function ($query) {
                $query->where('is_enable', 1);
            }])
            ->where('is_enable', 1)
            ->having('products_count', '>', 0)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return $this->generateResponse($shops->items(), 200, false, $shops->lastPage());
    }

    public function show(Shop $shop)
    {
        if (!$shop->is_enable) {
            abort(404);
        }

        return $this->generateResponse($shop->load('availableCategories', 'availableTags'), 200);
    }

    public function getCategories()
    {
        $shopCategories = ShopCategory::exclude(['created_by', 'updated_by'])
            ->withCount(['products' => function ($query) {
                $query->where('is_enable', 1)
                    ->whereHas('shop', function ($q) {
                        $q->where('is_enable', 1);
                    });
            }])
            ->whereHas('products', function ($query) {
                $query->where('is_enable', 1)
                    ->whereHas('shop', function ($q) {
                        $q->where('is_enable', 1);
                    });
            })
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->get();

        return $this->generateResponse($shopCategories, 200, false, 1);
    }

    public function getCatgorizedProduct(Request $request)
    {
        $shopCategories = ShopCategory::exclude(['created_by', 'updated_by'])
            ->whereHas('products', function ($query) {
                $query->where('is_enable', 1)
                    ->whereHas('shop', function ($q) {
                        $q->where('is_enable', 1);
                    });
            })
            ->orderBy('name', 'asc')
            ->paginate(10);

        $categorizedProducts = $shopCategories->map(function ($category) {
            $category->products = Product::exclude(['created_by', 'updated_by'])
                ->where('shop_category_id', $category->id)
                ->where('is_enable', 1)
                ->whereHas('shop', function ($query) {
                    $query->where('is_enable', 1);
                })
                ->orderBy('search_index', 'desc')
                ->orderBy('shop_sub_category_id', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            return $category;
        });

        return $this->generateProductResponse($categorizedProducts, 200, 'arrobj');
    }

    public function getTags(Request $request)
    {
        $size = $request->size ? $request->size : 10;
        $page = $request->page ? $request->page : 1;

        $shopTags = ShopTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy('id', 'desc')
            ->get();

        $shopTags = $shopTags->map(function ($shopTag) {
            $shopIds = CacheHelper::getShopIdsByTag($shopTag->id);

            $shopTag->products = $shopIds->map(function ($shopId) {
                return Product::where('shop_id', $shopId)
                    ->whereHas('shop', function ($query) {
                        $query->where('is_enable', 1);
                    })
                    ->where('is_enable', 1)
                    ->get();
            })->collapse()->sortByDesc('id')->take(50)->values();

            return $shopTag;
        })->slice(($page - 1) * $size, $size)->values();

        return $this->generateProductResponse($shopTags, 200, 'arrobj');
    }

    public function getByTag(Request $request, ShopTag $shopTag)
    {
        $size = $request->size ? $request->size : 100;
        $page = $request->page ? $request->page : 1;

        $shopIds = CacheHelper::getShopIdsByTag($shopTag->id);

        $shopTag->products = $shopIds->map(function ($shopId) {
            return Product::with('shop')
                ->where('shop_id', $shopId)
                ->whereHas('shop', function ($query) {
                    $query->where('is_enable', 1);
                })
                ->where('is_enable', 1)
                ->get();
        })->collapse()->sortByDesc('id')->slice(($page - 1) * $size, $size)->values();

        return $this->generateProductResponse($shopTag, 200, 'cattag');
    }

    public function getByCategory(Request $request, ShopCategory $shopCategory)
    {
        $shopCategory->products = Product::with('shop')
            ->where('shop_category_id', $shopCategory->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($shopCategory, 200, 'cattag');
    }

    public function getBySubCategory(Request $request, ShopSubCategory $shopSubCategory)
    {
        $shopSubCategory->products = Product::with('shop')
            ->where('shop_sub_category_id', $shopSubCategory->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($shopSubCategory->load('shopCategory'), 200, 'cattag');
    }
}
