<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;

class ProductVariationValueController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        return ProductVariationValue::with('productVariation')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue = ProductVariationValue::create($validatedData);

        if (!empty($request->image_slug)) {
            $this->updateFile($request->image_slug, 'product_variation_values', $productVariationValue->slug);
        }

        return response()->json($productVariationValue->load('productVariation'), 201);
    }

    public function show(ProductVariationValue $productVariationValue)
    {
        return response()->json($productVariationValue->with('productVariation'), 200);
    }

    public function update(Request $request, ProductVariationValue $productVariationValue)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue->update($request->all());

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'product_variation_values', $productVariationValue->slug);
        }

        return response()->json($productVariationValue->load('productVariation'), 200);
    }

    public function destroy(ProductVariationValue $productVariationValue)
    {
        foreach ($productVariationValue->images as $image) {
            $this->deleteFile($image->slug);
        }

        $productVariationValue->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'value' => 'required',
            'price' => 'required|numeric',
            'product_variation_slug' => 'required|exists:App\Models\ProductVariation,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
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

    public function getVariationValuesByVariation(Request $request, ProductVariation $productVariation)
    {
        return ProductVariationValue::where('product_variation_id', $productVariation->id)
            ->where(function ($query) use ($request) {
                return $query->where('value', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }
}
