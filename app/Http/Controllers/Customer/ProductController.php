<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $product =  Product::with('shop', 'shopCategory', 'brand', 'subCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)->items();
        return $this->generateResponse($product,200);
    }
    public function show($slug){
        return Product::with('shop', 'shopCategory', 'brand', 'subCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('slug', $slug)->first();
    }

    public function getByCategory(Request $request, $slug)
    {

        $product = Product::where('shop_category_id', $slug)->paginate($request->size)->items();

        return $this->generateResponse($product, 200);
    }

    public function getByShop(Request $request,$slug)
    {
        $shopId = $this->getShopId($slug);
        $product = Product::where('shop_id', $shopId)->paginate($request->size)->items();

        return $this->generateResponse($product,200);
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }
}
