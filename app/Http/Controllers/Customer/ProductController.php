<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\File;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $sorting = CollectionHelper::getSorting('products', 'name', $request->by ? $request->by : 'asc', $request->order);

        if ($sorting['orderBy'] === 'price') {
            $sorting['orderBy'] = 'pv.price';
        } else {
            $sorting['orderBy'] = 'products.' . $sorting['orderBy'];
        }

        $products = Product::select('products.*')
            ->join('product_variants as pv', function ($query) {
                $query->on('pv.id', '=', DB::raw('(SELECT id FROM product_variants WHERE product_variants.product_id = products.id ORDER BY price ASC LIMIT 1)'));
            })
            ->with('shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues', 'productVariants')
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhereHas('shop', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('shopCategory', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('shopSubCategory', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('brand', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    });
            })
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('products.is_enable', 1)
            ->whereNotNull('pv.price')
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate($request->size);

        return $this->generateProductResponse($products, 200, 'array', $products->lastPage());
    }

    public function show(Product $product)
    {
        if (!$product->is_enable || !$product->shop->is_enable) {
            abort(404);
        }

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

        return $this->generateProductResponse($product->load('shop', 'shopCategory', 'brand', 'shopSubCategory', 'productVariations', 'productVariants'), 200, 'other');
    }

    public function getByCategory(Request $request, ShopCategory $category)
    {
        $products = Product::with('shop')
            ->where('shop_category_id', $category->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        return $this->generateProductResponse($products, 200, 'array', $products->lastPage());
    }

    public function getByShop(Request $request, Shop $shop)
    {
        $product = Product::where('shop_id', $shop->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($product, 200);
    }

    public function getAllBrand()
    {
        $brand = Brand::orderBy('id', 'desc')->paginate(10)->items();
        return $this->generateResponse($brand, 200);
    }

    public function getByBrand(Request $request, Brand $brand)
    {
        $product = Product::where('brand_id', $brand->id)
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($product, 200);
    }

    // fav
    public function getFavorite(Request $request)
    {
        $favoriteProducts = $this->customer->favoriteProducts()
            ->with('shopCategory', 'shopSubCategory', 'brand')
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        return $this->generateProductResponse($favoriteProducts, 200, 'array', $favoriteProducts->lastPage());
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
        $products = Product::with('shop')
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->paginate($request->size);

        return $this->generateProductResponse($products, 200, 'array', $products->lastPage());
    }
}
