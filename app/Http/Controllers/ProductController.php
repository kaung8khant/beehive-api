<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Shop;
use App\Models\ShopSubCategory;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;

class ProductController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
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

        if ($request->brand_slug) {
            $validatedData['brand_id'] =  $this->getBrandId($request->brand_slug);
        }


        $product = Product::create($validatedData);
        $productId = $product->id;

        if ($request->product_variations) {
            $this->createProductVariation($productId, $validatedData['product_variations']);
        }

        return response()->json($product->refresh()->load('shop', "productVariations"), 201);
    }

    public function show($slug)
    {
        $product = Product::with('shop', 'shopCategory', 'shopSubCategory', 'brand')
        ->with('productVariations')->with('productVariations.productVariationValues')
        ->where('slug', $slug)->firstOrFail();
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
        if ($request->brand_slug) {
            $validatedData['brand_id'] = $this->getBrandId($request->brand_slug);
        }


        $product->update($validatedData);

        $productId = $product->id;

        if ($request->product_variations) {
            $product->productVariations()->delete();
            $this->createProductVariation($productId, $validatedData['product_variations']);
        }
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
            'sub_category_slug' => 'required|exists:App\Models\ShopSubCategory,slug',
            'brand_slug' => 'nullable|exists:App\Models\Brand,slug',

            'product_variations' => 'nullable|array',
            'product_variations.*.slug' => '',
            'product_variations.*.name' => 'nullable|string',
            'product_variations.*.name_mm' => 'nullable|string',


            'product_variations.*.product_variation_values' => 'nullable|array',
            'product_variations.*.product_variation_values.*.value' => 'nullable|string',
            'product_variations.*.product_variation_values.*.price' => 'nullable|numeric',

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
        return ShopSubCategory::where('slug', $slug)->first();
    }

    /**
    * Display a listing of the products by one shop.
    */
    public function getProductsByShop(Request $request, $slug)
    {
        return Product::with('shop', 'shopCategory', 'shopSubCategory', 'brand')->whereHas('shop', function ($q) use ($slug) {
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
        return Product::with('shopCategory', 'brand')->whereHas('shop_branches', function ($q) use ($slug) {
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

    private function createProductVariation($productId, $productVariations)
    {
        foreach ($productVariations as $variation) {
            $variation['slug'] = $this->generateUniqueSlug();
            $variation['product_id'] = $productId;
            $productVariation = ProductVariation::create($variation);
            $variationId = $productVariation->id;
            $this->createVariationValues($variationId, $variation['product_variation_values']);
        }
    }

    private function createVariationValues($variationId, $variationValues)
    {
        foreach ($variationValues as $variationValue) {
            $variationValue['slug'] = $this->generateUniqueSlug();
            $variationValue['product_variation_id'] = $variationId;
            ProductVariationValue::create($variationValue);
        }
    }

    public function toggleEnable($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $product->is_enable = !$product->is_enable;
        $product->save();
        return response()->json(['message' => 'Success.'], 200);
    }
    /**
      * Display a listing of products by each brand.
      */
    public function getProductsByBrand(Request $request, $slug)
    {
        return Product::with('shop', 'shopCategory')->whereHas('brand', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
