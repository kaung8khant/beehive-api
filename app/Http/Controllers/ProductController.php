<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SubCategory;

class ProductController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Product::with('shop', 'shop_category', 'product_variation')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(TRUE));

        $subCategory = $this->getSubCategory($request->sub_category_slug);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['shop_category_id'] = $subCategory->shop_category->id;
        $validatedData['sub_category_id'] = $subCategory->id;

        $product = Product::create($validatedData);
        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $product = Product::with('shop', 'shop_category', 'product_variation',"sub_category")->where('slug', $slug)->firstOrFail();
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $subCategory = $this->getSubCategory($request->sub_category_slug);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['shop_category_id'] = $subCategory->shop_category->id;
        $validatedData['sub_category_id'] = $subCategory->id;

        $product->update($validatedData);
        return response()->json($product, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Product::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'description' => 'required|string',
            'description_mm' => 'nullable|string',
            'price' => 'required|max:99999999',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'sub_category_slug' => 'required|exists:App\Models\SubCategory,slug',
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
}
