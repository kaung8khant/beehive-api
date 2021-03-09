<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        return Product::with('shop', 'shopCategory', 'brand', 'subCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(1);
    }
    public function show($slug){
        return Product::with('shop', 'shopCategory', 'brand', 'subCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('slug', $slug)->first();
    }

    public function getByCategory(Request $request, $slug)
    {

        $product = Product::where('shop_category_id', $slug)->paginate($request->size);

        return $this->generateResponse($product, 200);
    }
}
