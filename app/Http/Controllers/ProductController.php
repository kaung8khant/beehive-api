<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Shop;
use App\Models\SubCategory;

class ProductController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return Product::with('shop', 'shopCategory', 'productVariation', 'brand')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $subCategory = $this->getSubCategory($request->sub_category_slug);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['shop_category_id'] = $subCategory->shopCategory->id;
        $validatedData['sub_category_id'] = $subCategory->id;
        $validatedData['brand_id'] =  $this->getBrandId($request->brand_slug);

        $product = Product::create($validatedData);
        return response()->json($product->refresh()->load('shop'), 201);
    }

    public function show($slug)
    {
        $product = Product::with('shop', 'shopCategory', 'productVariation', 'subCategory', 'brand')->where('slug', $slug)->firstOrFail();
        return response()->json($product, 200);
    }

    public function update(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $subCategory = $this->getSubCategory($request->sub_category_slug);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['shop_category_id'] = $subCategory->shopCategory->id;
        $validatedData['sub_category_id'] = $subCategory->id;
        $validatedData['brand_id'] = $this->getBrandId($request->brand_slug);

        $product->update($validatedData);
        return response()->json($product, 200);
    }

    public function destroy($slug)
    {
        Product::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'description' => 'required|string',
            'description_mm' => 'nullable|string',
            'price' => 'required|max:99999999',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'sub_category_slug' => 'required|exists:App\Models\SubCategory,slug',
            'brand_slug' => 'required|exists:App\Models\Brand,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:products';
        }

        return $params;
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->first()->id;
    }

    private function getSubCategory($slug)
    {
        return SubCategory::where('slug', $slug)->first();
    }

    /**
    * Display a listing of the products by one shop.
    */
    public function getProductsByShop(Request $request, $slug)
    {
        return Product::with('shopCategory', 'brand')->whereHas('shop', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
    * Display available products by one shop branch.
    */
    public function getAvailableProductsByShopBranch(Request $request, $slug)
    {
        return Product::with('shopCategory', 'brand')->whereHas('shop', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    private function getBrandId($slug)
    {
        return Brand::where('slug', $slug)->first()->id;
    }
}
