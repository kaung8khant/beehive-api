<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ProductVariation;
use App\Models\Product;
use App\Models\ProductVariationValue;

class ProductVariationController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ProductVariation::with('product')
            ->where('name', 'LIKE', '%' . $request->filterr . '%')
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:product_variations',
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'product_slug' => 'required|exists:App\Models\Product,slug',

            'product_variation_values' => 'required|array',
            'product_variation_values.*.value' => 'required|string',
            'product_variation_values.*.price' => 'required|numeric',


        ]);

        $validatedData['product_id'] = $this->getProductId($request->product_slug);

        $productVariation = ProductVariation::create($validatedData);

        $variationId = $productVariation->id;

        $this->createVariationValues($variationId, $validatedData['product_variation_values']);

        return response()->json($productVariation->load('product','productVariationValues'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductVariation  $productVariation
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $productVariation = ProductVariation::with('product')->where('slug', $slug)->firstOrFail();
        return response()->json($productVariation, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductVariation  $productVariation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $productVariation = ProductVariation::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'product_slug' => 'required|exists:App\Models\Product,slug',
        ]);

        $validatedData['product_id'] = $this->getProductId($request->product_slug);

        $productVariation->update($validatedData);
        return response()->json($productVariation->load('product'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductVariation  $productVariation
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        ProductVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }

    public function getProductVariationsByProduct($slug, Request $request)
    {
        return ProductVariation::with('productVariationValues')
        ->whereHas('product', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where('name', 'LIKE', '%' . $request->filter . '%')
        ->orWhere('slug', $request->filter)
        ->paginate(10);
    }

    private function createVariationValues($variationId, $variationValues)
    {
        foreach ($variationValues as $variationValue) {
            $variationValue['slug'] = $this->generateUniqueSlug();
            $variationValue['product_variation_id'] = $variationId;
            ProductVariationValue::create($variationValue);
        }
    }

}
