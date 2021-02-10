<?php

namespace App\Http\Controllers;

use App\Models\ProductVariationValue;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use Illuminate\Validation\Rule;

class ProductVariationValueController extends Controller
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
        return ProductVariationValue::with('product_variation')
        ->where('name', 'LIKE', '%' . $filter . '%')
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
            'slug' => 'required|unique:product_variations',
            'name'=>'required',
            'price'=>'required|max:99999999',
            'product_variation_id' => 'required|exists:App\Models\ProductVariation,id'
        ]);

        $productVariationValue = ProductVariationValue::create($request->all());

        return response()->json($productVariationValue, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductVariationValue  $productVariationValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(ProductVariationValue::with('product_variation')->where('slug', $slug)->firstOrFail(), 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductVariationValue  $productVariationValue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$slug)
    {
        $productVariationValue = ProductVariationValue::where("slug",$slug)->firstOrFail();

        $request->validate([
            'name' => ['required',
            Rule::unique('product_variation_values')->ignore($productVariationValue->id),
        ],
            'price'=>'required|max:99999999',
            'product_variation_id' => 'required|exists:App\Models\ProductVariation,id',
        ]);

        $productVariationValue = ProductVariationValue::where('slug', $slug)->update($request->all());

        return response()->json($productVariationValue, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductVariationValue  $productVariationValue
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        ProductVariationValue::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}