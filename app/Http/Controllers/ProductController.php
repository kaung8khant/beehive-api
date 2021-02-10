<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use Illuminate\Validation\Rule;

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
        $filter=$request->filter;
        return Product ::with('shop','shop_category','product_variation')
        ->where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
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

        $request->validate([
            'slug' => 'required|unique:products',
            'name'=>'required',
            'price'=>'required|max:99999999',
            'shop_id' => 'required|exists:App\Models\Shop,id',
            'shop_category_id' => 'required|exists:App\Models\ShopCategory,id'
        ]);

        $product = Product::create($request->all());

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
        return response()->json(Product::with('shop','product_variation')->where('slug', $slug)->firstOrFail(), 200);
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

        $request->validate([
            'name' => ['required',
            Rule::unique('products')->ignore($product->id),
        ],
            'price'=>'required|max:99999999',
            'shop_id' => 'required|exists:App\Models\Shop,id',
            'shop_category_id' => 'required|exists:App\Models\ShopCategory,id'

        ]);

        $product = Product::where('slug', $slug)->update($request->all());

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
}
