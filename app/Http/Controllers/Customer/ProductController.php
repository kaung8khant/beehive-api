<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
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

}
