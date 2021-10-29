<?php

namespace App\Http\Controllers\Customer;

use App\Events\KeywordSearched;
use App\Helpers\AuthHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\File;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class ProductController extends Controller
{
    use ResponseHelper;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        if ($request->filter) {
            $products = Product::search($request->filter)->where('is_enable', 1)->where('is_shop_enable', 1)->paginate($request->size);

            KeywordSearched::dispatch(AuthHelper::getCustomerId(), $request->device_id, $request->filter);
        } else {
            $products = Product::select(CollectionHelper::selectExclusiveColumns('products'))
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

    public function show(Product $product)
    {
        if (!$product->is_enable || !$product->shop->is_enable) {
            abort(404);
        }

        $product->load(['shop', 'shopCategory', 'brand', 'shopSubCategory', 'productVariations', 'productVariants' => function ($query) {
            $query->where('is_enable', 1);
        }]);

        if ($product->variants) {
            foreach ($product->variants as $variants) {
                if ($variants && $variants['ui'] === 'image') {
                    $values = [];

                    foreach ($variants['values'] as $value) {
                        if (isset($value['image_slug'])) {
                            $image = File::where('slug', $value['image_slug'])->value('slug');
                            $url = "/api/v2/images/{$image}";
                            $value['url'] = config('app.url') . $url;
                        }

                        $values[] = $value;
                    }

                    $data = [
                        'ui' => $variants['ui'],
                        'name' => $variants['name'],
                        'values' => $values,
                    ];

                    $variants = $data;
                }
            }
        }

        return $this->generateProductResponse($product, 200, 'other');
    }

    public function getByCategory(Request $request, ShopCategory $category)
    {
        $products = Product::where('shop_category_id', $category->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('shop_sub_category_id', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $this->optimizeProducts($products);
        return $this->generateProductResponse($imageFilteredProducts, 200, 'array', $products->lastPage(), true);
    }

    public function getByShop(Request $request, Shop $shop)
    {
        $products = Product::where('shop_id', $shop->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('shop_sub_category_id', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $products->map(function ($product) {
            $product->makeHidden(['description', 'variants', 'created_by', 'updated_by', 'created_at', 'updated_at', 'covers']);
            return $product->images->count() > 0 ? $product : null;
        })->filter()->values();

        $data = new stdClass();
        $data->products = $imageFilteredProducts;
        $data->total = $products->total();
        $data->join_date = Carbon::parse($shop->created_at)->format('Y-m-d');

        return $this->generateProductResponse($data, 200, 'cattag', $products->lastPage());
    }

    public function getAllBrands()
    {
        $brands = Brand::exclude(['created_by', 'updated_by'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return $this->generateResponse($brands->items(), 200);
    }

    public function getByBrand(Request $request, Brand $brand)
    {
        $products = Product::where('brand_id', $brand->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('shop_sub_category_id', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $this->optimizeProducts($products);
        return $this->generateProductResponse($imageFilteredProducts, 200);
    }

    public function getFavorite(Request $request)
    {
        $favoriteProducts = $this->customer->favoriteProducts()
            ->with(['shop' => function ($query) {
                $query->select('id', 'slug', 'name');
            }])
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('shop_sub_category_id', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        $imageFilteredProducts = $favoriteProducts->map(function ($product) {
            $product->makeHidden(['description', 'variants', 'created_by', 'updated_by', 'covers', 'pivot']);
            $product->shop->makeHidden(['rating', 'images', 'covers', 'first_order_date']);
            return $product->images->count() > 0 ? $product : null;
        })->filter()->values();

        return $this->generateProductResponse($imageFilteredProducts, 200, 'array', $favoriteProducts->lastPage(), true);
    }

    public function setFavorite(Product $product)
    {
        try {
            $this->customer->favoriteProducts()->attach($product->id);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->generateResponse('You already set favorite this product.', 409, true);
        }

        return $this->generateResponse('Success.', 200, true);
    }

    public function removeFavorite(Product $product)
    {
        $this->customer->favoriteProducts()->detach($product->id);
        return $this->generateResponse('Success.', 200, true);
    }

    public function getRecommendations(Request $request)
    {
        $products = Product::where('is_enable', 1)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->inRandomOrder()
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
