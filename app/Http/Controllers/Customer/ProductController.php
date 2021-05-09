<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $product = Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')
            ->with('productVariations.productVariationValues')
            ->where('is_enable', 1)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($product, 200);
    }

    public function show($slug)
    {
        $product = Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')
            ->with('productVariations.productVariationValues')
            ->where('slug', $slug)
            ->where('is_enable', 1)
            ->firstOrFail();

        return $this->generateProductResponse($product, 200, 'other');
    }

    public function getByCategory(Request $request, $slug)
    {
        $category_id = $this->getShopCategoryId($slug);
        $product = Product::where('shop_category_id', $category_id)->where('is_enable', 1)->paginate($request->size)->items();

        return $this->generateProductResponse($product, 200);
    }

    public function getByShop(Request $request, $slug)
    {
        $shopId = $this->getShopId($slug);
        $product = Product::where('shop_id', $shopId)->where('is_enable', 1)->paginate($request->size)->items();

        return $this->generateProductResponse($product, 200);
    }
    public function getAllBrand()
    {
        $brand = Brand::all();
        return $this->generateResponse($brand, 200);
    }

    public function getByBrand(Request $request, $slug)
    {
        $brandId = $this->getBrandId($slug);
        $product = Product::where('brand_id', $brandId)->where('is_enable', 1)->paginate($request->size)->items();

        return $this->generateProductResponse($product, 200);
    }

    // fav
    public function getFavorite(Request $request)
    {
        $fav = $this->customer->favoriteProducts()->with('shopCategory', 'shopSubCategory', 'brand')->paginate($request->size)->items();
        return $this->generateProductResponse($fav, 200);
    }

    public function setFavorite($slug)
    {
        $productId = $this->getProductId($slug);

        try {
            $this->customer->favoriteProducts()->attach($productId);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->generateResponse('You already set favorite this product.', 409, true);
        }

        return $this->generateResponse('Success.', 200, true);
    }

    public function removeFavorite($slug)
    {
        $productId = $this->getProductId($slug);
        $this->customer->favoriteProducts()->detach($productId);
        return $this->generateResponse('Success.', 200, true);
    }

    public function getRecommendations(Request $request)
    {
        $product = Product::with('shop')
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->paginate($request->size)
            ->items();

        return $this->generateProductResponse($product, 200);
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->where('is_enable', 1)->firstOrFail()->id;
    }

    private function getBrandId($slug)
    {
        return Brand::where('slug', $slug)->firstOrFail()->id;
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->firstOrFail()->id;
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->where('is_enable', 1)->firstOrFail()->id;
    }
}
