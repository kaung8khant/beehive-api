<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ProductVariationValue;
use App\Models\ProductVariation;

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
        return ProductVariationValue::with('product_variation')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
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
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue = ProductVariationValue::create($validatedData);
        return response()->json($productVariationValue->load('product_variation'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductVariationValue  $productVariationValue
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $productVariationValue = ProductVariationValue::with('product_variation')->where('slug', $slug)->firstOrFail();
        return response()->json($productVariationValue, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductVariationValue  $productVariationValue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $productVariationValue = ProductVariationValue::where("slug", $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue->update($request->all());
        return response()->json($productVariationValue->load('product_variation'), 200);
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

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'name' => 'required|string',
            'value' => 'required',
            'price' => 'required|numeric',
            'product_variation_slug' => 'required|exists:App\Models\ProductVariation,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:product_variation_values';
        }

        return $params;
    }

    private function getProductVariationId($slug)
    {
        return ProductVariation::where('slug', $slug)->first()->id;
    }
}
