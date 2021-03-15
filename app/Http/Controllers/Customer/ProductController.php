<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\Brand;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $product =  Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)->items();
        return $this->generateResponse($product, 200);
    }

    public function show($slug)
    {
        $product =  Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('slug', $slug)->first();
        return $this->generateResponse($product, 200);
    }

    public function getByCategory(Request $request, $slug)
    {
        $category_id = $this->getShopCategoryId($slug);
        $product = Product::where('shop_category_id', $category_id)->paginate($request->size)->items();

        return $this->generateResponse($product, 200);
    }

    public function getByShop(Request $request, $slug)
    {
        $shopId = $this->getShopId($slug);
        $product = Product::where('shop_id', $shopId)->paginate($request->size)->items();

        return $this->generateResponse($product, 200);
    }
    public function getAllBrand(Request $request){
        Log::info("here");
        $brand = Brand::all();
        Log::info(json_encode($brand));
        return $this->generateResponse($brand, 200);
    }
    public function getByBrand(Request $request,$slug){
        $brandId = $this->getBrandId($slug);
        $product =  Product::where("brand_id",$brandId)->paginate($request->size)->items();
        return $this->generateResponse($product, 200);
    }

    private function getBrandId($slug){
        return Brand::where('slug', $slug)->firstOrFail()->id;
    }

    private function getShopCategoryId($slug){
        return ShopCategory::where('slug',$slug)->firstOrFail()->id;
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }
}
